<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Mcp\Mcp\Tools\Menu\Create;
use Blackcube\Mcp\Mcp\Tools\Menu\Delete;
use Blackcube\Mcp\Mcp\Tools\Menu\Get;
use Blackcube\Mcp\Mcp\Tools\Menu\Index;
use Blackcube\Mcp\Mcp\Tools\Menu\Move;
use Blackcube\Mcp\Mcp\Tools\Menu\Update;
use Blackcube\Mcp\Services\MenuService;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Real round-trip for the menu.* tools (a Hazeltree capped at 4 levels). Every
 * write goes through the actual tool (create / update / move / delete), is
 * checked against the real menus rows (level / path / left / right), and read
 * back through get / list — proving mcp output, DB state and re-read agree (ISO).
 * No mock, no stub: real MySQL schema and real ActiveRecord.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class MenuRoundTripCest
{
    use DatabaseCestTrait;

    public function testCreateUpdateMoveDeleteRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a menu tree through the mcp tools: create -> update -> move -> delete, ISO');


        $root = (new Create(new MenuService(), new NullLogger()))->handle('Root', 'fr', null, '/root');
        $rootId = $root['id'];
        $I->assertSame(1, $root['hazeltree']['level']);
        $I->assertSame([
            'id' => 'fr',
            'name' => 'Français',
        ], $root['language']);

        $second = (new Create(new MenuService(), new NullLogger()))->handle('Second', 'fr', null, '/second');
        $secondId = $second['id'];
        $I->assertSame(1, $second['hazeltree']['level']);

        $rootLevel = $this->db
            ->createCommand('SELECT level FROM menus WHERE id = ' . $rootId)
            ->queryScalar();
        $I->assertSame(1, (int) $rootLevel);

        $secondLevel = $this->db
            ->createCommand('SELECT level FROM menus WHERE id = ' . $secondId)
            ->queryScalar();
        $I->assertSame(1, (int) $secondLevel);

        $rootPath = $this->db
            ->createCommand('SELECT path FROM menus WHERE id = ' . $rootId)
            ->queryScalar();
        $I->assertNotEmpty($rootPath);

        $updated = (new Update(new MenuService(), new NullLogger()))->handle($secondId, 'Second updated', null, null, '/second-v2');
        $I->assertSame('Second updated', $updated['name']);
        $I->assertSame('/second-v2', $updated['route']);

        $name = $this->db
            ->createCommand('SELECT name FROM menus WHERE id = ' . $secondId)
            ->queryScalar();
        $I->assertSame('Second updated', $name);

        $route = $this->db
            ->createCommand('SELECT route FROM menus WHERE id = ' . $secondId)
            ->queryScalar();
        $I->assertSame('/second-v2', $route);

        $moved = (new Move(new MenuService(), new NullLogger()))->handle($secondId, $rootId, 'into');
        $I->assertSame(2, $moved['hazeltree']['level']);

        $secondLevel = $this->db
            ->createCommand('SELECT level FROM menus WHERE id = ' . $secondId)
            ->queryScalar();
        $I->assertSame(2, (int) $secondLevel);

        $rootLeft = $this->db
            ->createCommand('SELECT `left` FROM menus WHERE id = ' . $rootId)
            ->queryScalar();
        $secondLeft = $this->db
            ->createCommand('SELECT `left` FROM menus WHERE id = ' . $secondId)
            ->queryScalar();
        $rootRight = $this->db
            ->createCommand('SELECT `right` FROM menus WHERE id = ' . $rootId)
            ->queryScalar();
        $I->assertTrue((float) $rootLeft < (float) $secondLeft);
        $I->assertTrue((float) $secondLeft < (float) $rootRight);

        $got = (new Get(new MenuService(), new NullLogger()))->handle($secondId);
        $I->assertSame($secondId, $got['id']);
        $I->assertSame(2, $got['hazeltree']['level']);

        $listed = (new Index(new MenuService()))->handle();
        $I->assertCount(2, $listed['items']);
        $I->assertSame($rootId, $listed['items'][0]['id']);
        $I->assertSame(1, $listed['items'][0]['hazeltree']['level']);
        $I->assertSame($secondId, $listed['items'][1]['id']);
        $I->assertSame(2, $listed['items'][1]['hazeltree']['level']);

        $refused = (new Delete(new MenuService(), new NullLogger()))->handle($rootId);
        $I->assertInstanceOf(CallToolResult::class, $refused);
        $I->assertSame(true, $refused->isError);

        $count = $this->db
            ->createCommand('SELECT COUNT(*) FROM menus')
            ->queryScalar();
        $I->assertSame(2, (int) $count);

        $deletedChild = (new Delete(new MenuService(), new NullLogger()))->handle($secondId);
        $I->assertSame($secondId, $deletedChild['id']);

        $count = $this->db
            ->createCommand('SELECT COUNT(*) FROM menus')
            ->queryScalar();
        $I->assertSame(1, (int) $count);

        $deletedRoot = (new Delete(new MenuService(), new NullLogger()))->handle($rootId);
        $I->assertSame($rootId, $deletedRoot['id']);

        $count = $this->db
            ->createCommand('SELECT COUNT(*) FROM menus')
            ->queryScalar();
        $I->assertSame(0, (int) $count);
    }
}
