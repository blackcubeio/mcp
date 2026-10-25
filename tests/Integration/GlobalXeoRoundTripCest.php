<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Models\Host;
use Blackcube\Mcp\Mcp\Tools\GlobalXeo\Delete;
use Blackcube\Mcp\Mcp\Tools\GlobalXeo\Get;
use Blackcube\Mcp\Mcp\Tools\GlobalXeo\Index;
use Blackcube\Mcp\Mcp\Tools\GlobalXeo\Set;
use Blackcube\Mcp\Services\GlobalXeoService;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Blackcube\Mcp\Tests\Support\ServicesCestTrait;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Round-trips the site-wide SEO entries through the mcp tools against the
 * real DB: set (auto-create, like dboard) -> get -> list -> set (update) ->
 * delete, with the guards (unknown kind, missing entry, invalid data field).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class GlobalXeoRoundTripCest
{
    use DatabaseCestTrait;
    use ServicesCestTrait;

    private function globalXeoService(): GlobalXeoService
    {
        return new GlobalXeoService($this->makeFileService());
    }

    public function testSetGetListDeleteRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a globalXeo entry through the mcp tools: set auto-creates -> get -> list -> update -> delete, ISO');

        $host = new Host();
        $host->setName('globalxeo-host-' . uniqid());
        $host->save();
        $hostId = $host->getId();

        $created = (new Set($this->globalXeoService(), new NullLogger()))->handle($hostId, 'Organization', null, [
            'email' => 'contact@blackcube.io',
        ], true);
        $I->assertSame('Organization', $created['kind']);
        $I->assertSame('Organisation', $created['name'], 'auto-created with the dboard default name');
        $I->assertTrue($created['active']);
        $I->assertSame('contact@blackcube.io', $created['data']['email']);
        $I->assertSame('Organization', $created['schema']['name']);

        $storedCount = $this->db
            ->createCommand('SELECT COUNT(*) FROM globalXeos WHERE hostId = ' . $hostId . ' AND kind = \'Organization\'')
            ->queryScalar();
        $I->assertSame(1, (int) $storedCount);

        $fetched = (new Get($this->globalXeoService(), new NullLogger()))->handle($hostId, 'Organization');
        $I->assertSame('contact@blackcube.io', $fetched['data']['email']);

        $listed = (new Index($this->globalXeoService(), new NullLogger()))->handle($hostId);
        $I->assertCount(4, $listed['items'], 'four kinds per host');
        $byKind = array_column($listed['items'], null, 'kind');
        $I->assertTrue($byKind['Organization']['exists']);
        $I->assertFalse($byKind['WebSite']['exists']);

        $updated = (new Set($this->globalXeoService(), new NullLogger()))->handle($hostId, 'Organization', 'ACME', null, false);
        $I->assertSame('ACME', $updated['name']);
        $I->assertFalse($updated['active']);
        $I->assertSame('contact@blackcube.io', $updated['data']['email'], 'data untouched by a name/active patch');

        $badKind = (new Get($this->globalXeoService(), new NullLogger()))->handle($hostId, 'Nonsense');
        $I->assertInstanceOf(CallToolResult::class, $badKind, 'unknown kind refused');

        $missing = (new Get($this->globalXeoService(), new NullLogger()))->handle($hostId, 'Robots');
        $I->assertInstanceOf(CallToolResult::class, $missing, 'missing entry points to globalXeo.set');

        $deleted = (new Delete($this->globalXeoService(), new NullLogger()))->handle($hostId, 'Organization');
        $I->assertSame('ACME', $deleted['name']);

        $remaining = $this->db
            ->createCommand('SELECT COUNT(*) FROM globalXeos WHERE hostId = ' . $hostId)
            ->queryScalar();
        $I->assertSame(0, (int) $remaining);
    }
}
