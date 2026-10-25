<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Mcp\Mcp\Tools\Language\Create;
use Blackcube\Mcp\Mcp\Tools\Language\Index;
use Blackcube\Mcp\Mcp\Tools\Language\Update;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Psr\Log\NullLogger;

/**
 * Real round-trip for the language.* tools: each write goes through the actual
 * tool (save), is checked against the real database row, and read back through
 * the read tool — proving mcp output, DB state and re-read agree (ISO). No mock,
 * no stub: a real MySQL schema (DatabaseCestTrait) and the real ActiveRecord.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class LanguageRoundTripCest
{
    use DatabaseCestTrait;

    public function testCreateUpdateRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a language through the mcp tools: save -> DB -> read, ISO');

        $created = (new Create(new NullLogger()))->handle('pt', 'Português', false, true);
        $I->assertSame([
            'id' => 'pt',
            'name' => 'Português',
            'main' => false,
            'active' => true,
        ], $created);

        $storedLanguage = $this->db
            ->createCommand("SELECT name, main, active FROM languages WHERE id = 'pt'")
            ->queryOne();
        $I->assertSame('Português', $storedLanguage['name']);
        $I->assertSame(0, (int) $storedLanguage['main']);
        $I->assertSame(1, (int) $storedLanguage['active']);

        $listed = (new Index())->handle();
        $I->assertContains($created, $listed['items']);

        $updated = (new Update(new NullLogger()))->handle('pt', 'Português (Brasil)', true, false);
        $I->assertSame([
            'id' => 'pt',
            'name' => 'Português (Brasil)',
            'main' => true,
            'active' => false,
        ], $updated);

        $storedLanguage = $this->db
            ->createCommand("SELECT name, main, active FROM languages WHERE id = 'pt'")
            ->queryOne();
        $I->assertSame('Português (Brasil)', $storedLanguage['name']);
        $I->assertSame(1, (int) $storedLanguage['main']);
        $I->assertSame(0, (int) $storedLanguage['active']);

        $listed = (new Index())->handle();
        $I->assertContains($updated, $listed['items']);
    }
}
