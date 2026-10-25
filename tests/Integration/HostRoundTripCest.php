<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Models\Host;
use Blackcube\Mcp\Mcp\Tools\Host\Create;
use Blackcube\Mcp\Mcp\Tools\Host\Index;
use Blackcube\Mcp\Mcp\Tools\Host\Update;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Psr\Log\NullLogger;

/**
 * Real round-trip for the host.* tools. Host is update-only (no create/delete
 * tool), so the row is seeded through the real dcore ActiveRecord, then the
 * update goes through the actual tool (save), is checked against the real hosts
 * row, and read back through the list tool — proving mcp output, DB state and
 * re-read agree (ISO). No mock, no stub: real MySQL schema and real ActiveRecord.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class HostRoundTripCest
{
    use DatabaseCestTrait;

    public function testUpdateRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a host through the mcp tools: seed AR -> update tool -> DB -> list, ISO');

        $host = new Host();
        $host->setName('rt.example.com');
        $host->save();
        $hostId = $host->getId();

        $updated = (new Update(new NullLogger()))->handle($hostId, 'rt-renamed.example.com', false, 'RT Site', 'RT Alt', 'RT Desc');
        $I->assertSame('rt-renamed.example.com', $updated['name']);
        $I->assertSame(false, $updated['active']);
        $I->assertSame('RT Site', $updated['siteName']);

        $name = $this->db
            ->createCommand('SELECT name FROM hosts WHERE id = ' . $hostId)
            ->queryScalar();
        $I->assertSame('rt-renamed.example.com', $name);

        $active = $this->db
            ->createCommand('SELECT active FROM hosts WHERE id = ' . $hostId)
            ->queryScalar();
        $I->assertSame(0, (int) $active);

        $siteDescription = $this->db
            ->createCommand('SELECT siteDescription FROM hosts WHERE id = ' . $hostId)
            ->queryScalar();
        $I->assertSame('RT Desc', $siteDescription);

        $listed = (new Index())->handle();
        $expected = [
            'id' => $hostId,
            'name' => 'rt-renamed.example.com',
            'active' => false,
            'siteName' => 'RT Site',
            'siteAlternateName' => 'RT Alt',
            'siteDescription' => 'RT Desc',
        ];
        $I->assertContains($expected, $listed['items']);
    }

    public function testCreateRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('create a host through the mcp tool and check the DB row, ISO');

        $created = (new Create(new NullLogger()))->handle('created.blackcube.io', 'Created Site', null, 'Created Desc');
        $hostId = $created['id'];
        $I->assertSame('created.blackcube.io', $created['name']);
        $I->assertSame('Created Site', $created['siteName']);
        $I->assertTrue($created['active']);

        $storedName = $this->db
            ->createCommand('SELECT name FROM hosts WHERE id = ' . $hostId)
            ->queryScalar();
        $I->assertSame('created.blackcube.io', $storedName);
    }
}
