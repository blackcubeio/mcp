<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dboard\Services\AccessTokenValidator;
use Blackcube\Mcp\Dboard\McpAddon;
use Blackcube\Mcp\Dboard\McpRbac;
use Blackcube\Mcp\Helpers\ApiKeyToken;
use Blackcube\Mcp\Models\ApiKey;
use Mcp\Server\Authorization\AccessToken;
use Mcp\Server\Transport\Http\OAuth\AuthorizationResult;
use Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface;
use Psr\Clock\ClockInterface;

/**
 * Validates the Bearer credential for the SDK AuthorizationMiddleware.
 *
 * A JWT is an access token issued by the dboard authorization server for the
 * MCP population: dboard checks it and hands back the granted permissions,
 * bounded by what the administrator holds right now. Anything else is an
 * opaque MCP key, looked up by SHA-256 hash, checked for revocation and
 * expiration, its last use refreshed. Both need MCP:CONNECT.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class AuthorizationTokenValidator implements AuthorizationTokenValidatorInterface
{
    private const JWT_SEPARATORS = 2;

    public function __construct(
        private readonly ClockInterface $clock,
        private readonly AccessTokenValidator $accessTokenValidator,
    ) {}

    public function validate(string $accessToken): AuthorizationResult
    {
        if (substr_count($accessToken, '.') === self::JWT_SEPARATORS) {
            $authorizationResult = $this->validateToken($accessToken);
        } else {
            $authorizationResult = $this->validateKey($accessToken);
        }

        return $authorizationResult;
    }

    private function validateToken(string $accessToken): AuthorizationResult
    {
        $authorizationResult = AuthorizationResult::unauthorized('invalid_token', 'Invalid or expired access token.');
        $grant = $this->accessTokenValidator->validate($accessToken, McpAddon::getPopulation());
        if ($grant !== null) {
            $authorizationResult = $this->allow($grant['permissions'], [
                'mcp.administratorId' => $grant['administrator']->getId(),
                'mcp.administratorName' => $grant['administrator']->getName(),
            ]);
        }

        return $authorizationResult;
    }

    private function validateKey(string $accessToken): AuthorizationResult
    {
        $authorizationResult = AuthorizationResult::unauthorized('invalid_token', 'Invalid MCP key.');
        $apiKey = ApiKey::query()->andWhere([
            'hash' => ApiKeyToken::hash($accessToken),
        ])->one();
        if ($apiKey !== null && $this->isUsable($apiKey) === true) {
            $apiKey->setDateLastUse($this->clock->now());
            $apiKey->save();
            $authorizationResult = $this->allow($apiKey->getScopes() ?? [], [
                'mcp.keyId' => $apiKey->getId(),
                'mcp.keyName' => $apiKey->getName(),
            ]);
        }

        return $authorizationResult;
    }

    /**
     * @param string[] $permissions
     * @param array<string, mixed> $claims
     */
    private function allow(array $permissions, array $claims): AuthorizationResult
    {
        if (in_array(McpRbac::PERMISSION_MCP_CONNECT, $permissions, true) === false) {
            $authorizationResult = AuthorizationResult::forbidden(
                'insufficient_scope',
                'The ' . McpRbac::PERMISSION_MCP_CONNECT . ' permission is required.',
                [
                    McpRbac::PERMISSION_MCP_CONNECT,
                ],
            );
        } else {
            $authorizationResult = AuthorizationResult::allow(new AccessToken(array_values($permissions), $claims));
        }

        return $authorizationResult;
    }

    private function isUsable(ApiKey $apiKey): bool
    {
        $usable = false;
        if ($apiKey->isRevoked() === false) {
            $dateExpire = $apiKey->getDateExpire();
            if ($dateExpire !== null && $dateExpire > $this->clock->now()) {
                $usable = true;
            }
        }

        return $usable;
    }
}
