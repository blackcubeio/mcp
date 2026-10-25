<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Mcp\Mcp\Tools\Parameter\Create;
use Blackcube\Mcp\Mcp\Tools\Parameter\Delete;
use Blackcube\Mcp\Mcp\Tools\Parameter\Index;
use Blackcube\Mcp\Mcp\Tools\Parameter\Update;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Psr\Log\NullLogger;

/**
 * Real round-trip for the parameter.* tools: each write goes through the actual
 * tool (save), is checked against the real database row, and read back through
 * the read tool — proving mcp output, DB state and re-read agree (ISO). No mock,
 * no stub: a real MySQL schema (DatabaseCestTrait) and the real ActiveRecord.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ParameterRoundTripCest
{
    use DatabaseCestTrait;

    public function testCreateUpdateDeleteRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a parameter through the mcp tools: save -> DB -> read, ISO');

        $created = (new Create(new NullLogger()))->handle('RT', 'KEY', 'v1');
        $I->assertSame([
            'domain' => 'RT',
            'name' => 'KEY',
            'value' => 'v1',
        ], $created);

        $storedValue = $this->db
            ->createCommand("SELECT value FROM parameters WHERE domain = 'RT' AND name = 'KEY'")
            ->queryScalar();
        $I->assertSame('v1', $storedValue);

        $listed = (new Index())->handle();
        $I->assertContains([
            'domain' => 'RT',
            'name' => 'KEY',
            'value' => 'v1',
        ], $listed['items']);

        $updated = (new Update(new NullLogger()))->handle('RT', 'KEY', 'v2');
        $I->assertSame('v2', $updated['value']);

        $storedValue = $this->db
            ->createCommand("SELECT value FROM parameters WHERE domain = 'RT' AND name = 'KEY'")
            ->queryScalar();
        $I->assertSame('v2', $storedValue);

        $listed = (new Index())->handle();
        $I->assertContains([
            'domain' => 'RT',
            'name' => 'KEY',
            'value' => 'v2',
        ], $listed['items']);

        (new Delete(new NullLogger()))->handle('RT', 'KEY');

        $count = $this->db
            ->createCommand("SELECT COUNT(*) FROM parameters WHERE domain = 'RT' AND name = 'KEY'")
            ->queryScalar();
        $I->assertSame(0, (int) $count);

        $listed = (new Index())->handle();
        $I->assertNotContains('RT', array_column($listed['items'], 'domain'));
    }
}
