<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Dboard\McpRbac;
use Blackcube\Mcp\Models\ApiKey;
use Blackcube\Mcp\Services\ApiKeyService;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;

/**
 * Integration tests for ApiKeyService: create, rotate, revoke, update, getKeys.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ApiKeyServiceCest
{
    use DatabaseCestTrait;

    public const NOW = '2026-06-26 12:00:00';

    private function makeService(): ApiKeyService
    {
        $clock = new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable(ApiKeyServiceCest::NOW);
            }
        };

        return new ApiKeyService($clock, 180, 90);
    }

    public function testCreatePersistsKeyAndReturnsToken(IntegrationTester $I): void
    {
        $I->wantTo('verify Service::create persists the key and returns the token once');
        $service = $this->makeService();

        $createdKey = $service->create('agent', [
            McpRbac::PERMISSION_MCP_CONNECT,
            Rbac::PERMISSION_XEO_VIEW,
        ], 30);

        $I->assertNotEmpty($createdKey['token']);
        $I->assertIsInt($createdKey['id']);

        $key = ApiKey::query()->andWhere(['id' => $createdKey['id']])->one();
        $I->assertNotNull($key);
        $I->assertEquals('agent', $key->getName());
        $I->assertEquals([
            McpRbac::PERMISSION_MCP_CONNECT,
            Rbac::PERMISSION_XEO_VIEW,
        ], $key->getScopes());
        $I->assertFalse($key->isRevoked());
        $I->assertEquals('2026-07-26', $key->getDateExpire()->format('Y-m-d'));
    }

    public function testCreateDefaultsTtlToDefaultTtlDays(IntegrationTester $I): void
    {
        $I->wantTo('verify Service::create falls back to defaultTtlDays when ttl is omitted');
        $service = $this->makeService();

        $createdKey = $service->create('agent', [
            McpRbac::PERMISSION_MCP_CONNECT,
        ]);

        $key = ApiKey::query()->andWhere(['id' => $createdKey['id']])->one();
        $I->assertEquals('2026-09-24', $key->getDateExpire()->format('Y-m-d'));
    }

    public function testCreateRejectsTtlAboveCap(IntegrationTester $I): void
    {
        $I->wantTo('verify Service::create rejects a ttl above maxTtlDays');
        $service = $this->makeService();

        $I->expectThrowable(InvalidArgumentException::class, function () use ($service) {
            $service->create('agent', [
                McpRbac::PERMISSION_MCP_CONNECT,
            ], 999);
        });
    }

    public function testRotateIssuesFreshTokenAndResetsExpiration(IntegrationTester $I): void
    {
        $I->wantTo('verify Service::rotate issues a fresh token, resets expiration and reactivates');
        $service = $this->makeService();
        $created = $service->create('agent', [
            McpRbac::PERMISSION_MCP_CONNECT,
        ], 10);
        $oldHash = ApiKey::query()->andWhere(['id' => $created['id']])->one()->getHash();

        $rotated = $service->rotate($created['id']);

        $I->assertNotEmpty($rotated['token']);
        $I->assertNotEquals($created['token'], $rotated['token']);

        $key = ApiKey::query()->andWhere(['id' => $created['id']])->one();
        $I->assertNotEquals($oldHash, $key->getHash());
        $I->assertFalse($key->isRevoked());
        $I->assertEquals('2026-09-24', $key->getDateExpire()->format('Y-m-d'));
    }

    public function testRevokeSoftRevokesKey(IntegrationTester $I): void
    {
        $I->wantTo('verify Service::revoke soft-revokes the key (record kept)');
        $service = $this->makeService();
        $created = $service->create('agent', [
            McpRbac::PERMISSION_MCP_CONNECT,
        ]);

        $isRevoked = $service->revoke($created['id']);

        $I->assertTrue($isRevoked);
        $I->assertTrue(ApiKey::query()->andWhere(['id' => $created['id']])->one()->isRevoked());
    }

    public function testRevokeMissingKeyReturnsFalse(IntegrationTester $I): void
    {
        $I->wantTo('verify Service::revoke returns false for an unknown key');
        $service = $this->makeService();

        $I->assertFalse($service->revoke(999999));
    }

    public function testUpdateChangesMetadataWithoutTouchingToken(IntegrationTester $I): void
    {
        $I->wantTo('verify Service::update changes name/scopes/expiration but keeps the token');
        $service = $this->makeService();
        $created = $service->create('agent', [
            McpRbac::PERMISSION_MCP_CONNECT,
        ], 30);
        $hash = ApiKey::query()->andWhere(['id' => $created['id']])->one()->getHash();

        $service->update($created['id'], 'renamed', [
            Rbac::PERMISSION_CONTENT_VIEW,
            Rbac::PERMISSION_CONTENT_UPDATE,
        ], '2026-12-01 12:00:00');

        $key = ApiKey::query()->andWhere(['id' => $created['id']])->one();
        $I->assertEquals('renamed', $key->getName());
        $I->assertEquals([
            Rbac::PERMISSION_CONTENT_VIEW,
            Rbac::PERMISSION_CONTENT_UPDATE,
        ], $key->getScopes());
        $I->assertEquals('2026-12-01', $key->getDateExpire()->format('Y-m-d'));
        $I->assertEquals($hash, $key->getHash());
    }

    public function testGetKeysExcludesExpiredByDefault(IntegrationTester $I): void
    {
        $I->wantTo('verify Service::getKeys excludes expired keys unless includeExpired');
        $service = $this->makeService();
        $service->create('active', [
            McpRbac::PERMISSION_MCP_CONNECT,
        ], 30);

        $expired = $service->create('expired', [
            McpRbac::PERMISSION_MCP_CONNECT,
        ], 1);
        $key = ApiKey::query()->andWhere(['id' => $expired['id']])->one();
        $key->setDateExpire(new DateTimeImmutable('2020-01-01'));
        $key->save();

        $I->assertCount(1, $service->getKeys(false));
        $I->assertCount(2, $service->getKeys(true));
    }
}
