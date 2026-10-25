<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Mcp\Dboard\McpAddon;
use Blackcube\Mcp\Helpers\ApiKeyToken;
use Blackcube\Mcp\Models\ApiKey;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;

/**
 * Manages the lifecycle of MCP keys: creation under the TTL cap, revocation,
 * listing and garbage collection of dead keys.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ApiKeyService
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly int $maxTtlDays,
        private readonly int $defaultTtlDays,
    ) {}

    /**
     * @param string[] $scopes
     *
     * @return array{token: string, id: int}
     */
    public function create(string $name, array $scopes, ?int $ttlDays = null): array
    {
        $ttlDays ??= $this->defaultTtlDays;
        if ($ttlDays > $this->maxTtlDays) {
            throw new InvalidArgumentException('ttlDays exceeds the hard cap of ' . $this->maxTtlDays . ' days.');
        }
        $this->assertScopes($scopes);

        $generated = ApiKeyToken::generate();
        $apiKey = new ApiKey();
        $apiKey->setName($name);
        $apiKey->setHash($generated['hash']);
        $apiKey->setScopes($scopes);
        $apiKey->setDateExpire($this->clock->now()->modify('+' . $ttlDays . ' days'));
        $apiKey->save();

        return [
            'token' => $generated['token'],
            'id' => $apiKey->getId(),
        ];
    }

    public function revoke(int $id): bool
    {
        $revoked = false;
        $apiKey = ApiKey::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($apiKey !== null) {
            $apiKey->setRevoked(true);
            $apiKey->save();
            $revoked = true;
        }

        return $revoked;
    }

    /**
     * Rotates a key: generates a fresh token (new hash), resets the expiration
     * under the TTL cap and reactivates it. The previous token stops working.
     *
     * @return array{token: string, id: int}|null Null when the key does not exist.
     */
    public function rotate(int $id, ?int $ttlDays = null): ?array
    {
        $rotated = null;
        $apiKey = ApiKey::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($apiKey !== null) {
            $ttlDays ??= $this->defaultTtlDays;
            if ($ttlDays > $this->maxTtlDays) {
                throw new InvalidArgumentException('ttlDays exceeds the hard cap of ' . $this->maxTtlDays . ' days.');
            }
            $generated = ApiKeyToken::generate();
            $apiKey->setHash($generated['hash']);
            $apiKey->setDateExpire($this->clock->now()->modify('+' . $ttlDays . ' days'));
            $apiKey->setRevoked(false);
            $apiKey->save();
            $rotated = [
                'token' => $generated['token'],
                'id' => $apiKey->getId(),
            ];
        }

        return $rotated;
    }

    /**
     * Updates the metadata of a key (name, scopes, expiration) without touching
     * its token. The expiration stays under the TTL cap.
     *
     * @param string[] $scopes
     */
    public function update(int $id, string $name, array $scopes, ?string $dateExpire = null): bool
    {
        $updated = false;
        $apiKey = ApiKey::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($apiKey !== null) {
            $this->assertScopes($scopes);
            $apiKey->setName($name);
            $apiKey->setScopes($scopes);
            if ($dateExpire !== null && $dateExpire !== '') {
                $expire = new \DateTimeImmutable($dateExpire);
                $maxExpire = $this->clock->now()->modify('+' . $this->maxTtlDays . ' days');
                if ($expire > $maxExpire) {
                    throw new InvalidArgumentException('Expiration exceeds the hard cap of ' . $this->maxTtlDays . ' days.');
                }
                $apiKey->setDateExpire($expire);
            }
            $apiKey->save();
            $updated = true;
        }

        return $updated;
    }

    /**
     * Loads a single key by its identifier.
     */
    public function getKey(int $id): ?ApiKey
    {
        return ApiKey::query()->andWhere([
            'id' => $id,
        ])->one();
    }

    /**
     * @return ApiKey[]
     */
    public function getKeys(bool $includeExpired = false): array
    {
        $keysQuery = ApiKey::query()->orderBy([
            'dateCreate' => SORT_DESC,
            'id' => SORT_DESC,
        ]);
        if ($includeExpired === false) {
            $keysQuery->andWhere([
                '>',
                'dateExpire',
                $this->clock->now()->format('Y-m-d H:i:s'),
            ]);
        }

        $keys = [];
        /** @var ApiKey $apiKey */
        foreach ($keysQuery->each() as $apiKey) {
            $keys[] = $apiKey;
        }

        return $keys;
    }

    /**
     * Ids of the given keys whose expiration is past, judged on the service clock.
     *
     * @param ApiKey[] $keys
     * @return int[]
     */
    public function getExpiredIds(array $keys): array
    {
        $now = $this->clock->now();
        $expiredIds = [];
        foreach ($keys as $apiKey) {
            $dateExpire = $apiKey->getDateExpire();
            if ($dateExpire !== null && $dateExpire < $now) {
                $expiredIds[] = $apiKey->getId();
            }
        }

        return $expiredIds;
    }

    public function gc(): int
    {
        $purged = 0;
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $apiKey = new ApiKey();

        $purged = $apiKey->deleteAll([
            'or',
            [
                'revoked' => true,
            ],
            [
                '<',
                'dateExpire',
                $now,
            ],
        ]);

        return $purged;
    }

    /**
     * @param string[] $scopes
     */
    private function assertScopes(array $scopes): void
    {
        $published = McpAddon::getScopes();
        foreach ($scopes as $scope) {
            if (in_array($scope, $published, true) === false) {
                throw new InvalidArgumentException('Unknown scope: ' . $scope);
            }
        }
    }
}
