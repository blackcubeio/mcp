<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Models\Content;
use Blackcube\Mcp\Mcp\Tools\LlmMenu\Create;
use Blackcube\Mcp\Mcp\Tools\LlmMenu\Delete;
use Blackcube\Mcp\Mcp\Tools\LlmMenu\Get;
use Blackcube\Mcp\Mcp\Tools\LlmMenu\Index;
use Blackcube\Mcp\Mcp\Tools\LlmMenu\Move;
use Blackcube\Mcp\Mcp\Tools\LlmMenu\Update;
use Blackcube\Mcp\Services\LlmMenuService;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Round-trips the llms.txt navigation tree through the mcp tools against the
 * real DB: create (root, category, data with a route target) -> get -> update
 * (rename, clear target) -> move -> delete, with the business guards (3
 * levels max, no delete with children, invalid route).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class LlmMenuRoundTripCest
{
    use DatabaseCestTrait;

    public function testCreateUpdateMoveDeleteRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a llmMenu tree through the mcp tools: create -> get -> update -> move -> delete, ISO');

        $content = new Content();
        $content->setName('llm-target');
        $content->save();
        $contentRoute = 'dcore-c-' . $content->getId();

        $root = (new Create(new LlmMenuService(), new NullLogger()))->handle('Documentation');
        $rootId = $root['id'];
        $I->assertSame(1, $root['hazeltree']['level']);
        $I->assertNull($root['target']);

        $category = (new Create(new LlmMenuService(), new NullLogger()))->handle('Guides', 'Developer guides', null, $rootId, 'into');
        $categoryId = $category['id'];
        $I->assertSame(2, $category['hazeltree']['level']);
        $I->assertSame('Developer guides', $category['description']);

        $createdLlmMenu = (new Create(new LlmMenuService(), new NullLogger()))->handle('Install', 'Setup steps', $contentRoute, $categoryId, 'into');
        $createdLlmMenuId = $createdLlmMenu['id'];
        $I->assertSame(3, $createdLlmMenu['hazeltree']['level']);
        $I->assertSame($contentRoute, $createdLlmMenu['target']['route']);
        $I->assertSame('llm-target', $createdLlmMenu['target']['name']);

        $storedContentId = $this->db
            ->createCommand('SELECT contentId FROM llmMenus WHERE id = ' . $createdLlmMenuId)
            ->queryScalar();
        $I->assertSame($content->getId(), (int) $storedContentId);

        $tooDeep = (new Create(new LlmMenuService(), new NullLogger()))->handle('Too deep', null, null, $createdLlmMenuId, 'into');
        $I->assertInstanceOf(CallToolResult::class, $tooDeep, 'level 4 refused');

        $badRoute = (new Create(new LlmMenuService(), new NullLogger()))->handle('Bad', null, 'not-a-route');
        $I->assertInstanceOf(CallToolResult::class, $badRoute, 'invalid route refused');

        $fetched = (new Get(new LlmMenuService(), new NullLogger()))->handle($createdLlmMenuId);
        $I->assertSame('Install', $fetched['name']);
        $I->assertSame($contentRoute, $fetched['target']['route']);

        $updated = (new Update(new LlmMenuService(), new NullLogger()))->handle($createdLlmMenuId, 'Install v2', null, '');
        $I->assertSame('Install v2', $updated['name']);
        $I->assertNull($updated['target'], 'empty route clears the target');

        $clearedContentId = $this->db
            ->createCommand('SELECT contentId FROM llmMenus WHERE id = ' . $createdLlmMenuId)
            ->queryScalar();
        $I->assertNull($clearedContentId);

        $moved = (new Move(new LlmMenuService(), new NullLogger()))->handle($createdLlmMenuId, $rootId, 'into');
        $I->assertSame(2, $moved['hazeltree']['level']);

        $listed = (new Index(new LlmMenuService()))->handle();
        $listedIds = array_column($listed['items'], 'id');
        $I->assertContains($rootId, $listedIds);
        $I->assertContains($categoryId, $listedIds);
        $I->assertContains($createdLlmMenuId, $listedIds);

        $refused = (new Delete(new LlmMenuService(), new NullLogger()))->handle($rootId);
        $I->assertInstanceOf(CallToolResult::class, $refused, 'delete refused while children exist');

        $deletedData = (new Delete(new LlmMenuService(), new NullLogger()))->handle($createdLlmMenuId);
        $I->assertSame('Install v2', $deletedData['name']);

        $remaining = $this->db
            ->createCommand('SELECT COUNT(*) FROM llmMenus WHERE id = ' . $createdLlmMenuId)
            ->queryScalar();
        $I->assertSame(0, (int) $remaining);
    }
}
