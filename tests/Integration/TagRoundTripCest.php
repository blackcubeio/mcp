<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Helpers\Element;
use Blackcube\Mcp\Mcp\Tools\Content\Create as ContentCreate;
use Blackcube\Mcp\Mcp\Tools\Content\Tag as ContentTag;
use Blackcube\Mcp\Mcp\Tools\Tag\Create as TagCreate;
use Blackcube\Mcp\Mcp\Tools\Tag\Get as TagGet;
use Blackcube\Mcp\Mcp\Tools\Tag\Update as TagUpdate;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Blackcube\Mcp\Tests\Support\ServicesCestTrait;
use Psr\Log\NullLogger;

/**
 * Real round-trip for the tag.* tools and the content/tag pivot: create a group
 * + a level-2 tag, update + read them, then attach the level-2 tag to a content
 * (content.tag) and check both the contents_tags pivot row and that tag.get sees
 * the content back. DB-verified, read back through the tools, ISO. No mock.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TagRoundTripCest
{
    use DatabaseCestTrait;
    use ServicesCestTrait;

    public function testCreateUpdateTagContentRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a tag through the mcp tools and attach it to a content, DB + read ISO');

        $base = $this->seedBaseData();
        $elementService = $this->makeElementService($this->makeFileService());

        $group = (new TagCreate($elementService, new NullLogger()))->handle('Topics', $base['typeId'], $base['languageId']);
        $I->assertIsArray($group);
        $groupRoute = $group['route'];
        $groupId = Element::createFromRoute($groupRoute)->getId();
        $groupLevel = (int) $this->db->createCommand('SELECT level FROM tags WHERE id = ' . $groupId)->queryScalar();
        $I->assertSame(1, $groupLevel);

        $child = (new TagCreate($elementService, new NullLogger()))->handle(
            'Php',
            $base['typeId'],
            $base['languageId'],
            null,
            false,
            null,
            null,
            $groupRoute,
            'into',
        );
        $I->assertIsArray($child);
        $childRoute = $child['route'];
        $childId = Element::createFromRoute($childRoute)->getId();
        $childLevel = (int) $this->db->createCommand('SELECT level FROM tags WHERE id = ' . $childId)->queryScalar();
        $I->assertSame(2, $childLevel);

        $updated = (new TagUpdate($elementService, new NullLogger()))->handle($childRoute, [
            'name' => 'PHP updated',
        ]);
        $I->assertIsArray($updated);
        $name = $this->db->createCommand('SELECT name FROM tags WHERE id = ' . $childId)->queryScalar();
        $I->assertSame('PHP updated', $name);

        $childNode = (new TagGet($elementService))->handle($childRoute);
        $I->assertIsArray($childNode);
        $I->assertSame($childRoute, $childNode['route']);
        $I->assertSame('PHP updated', $childNode['name']);
        $I->assertSame([], $childNode['contents']);

        $content = (new ContentCreate($elementService, new NullLogger()))->handle('Article', $base['typeId'], $base['languageId']);
        $contentRoute = $content['route'];
        $contentId = Element::createFromRoute($contentRoute)->getId();

        $tagged = (new ContentTag($elementService, new NullLogger()))->handle($contentRoute, [
            [
                'route' => $childRoute,
            ],
        ]);
        $I->assertIsArray($tagged);

        $pivotCount = (int) $this->db
            ->createCommand('SELECT COUNT(*) FROM contents_tags WHERE contentId = ' . $contentId . ' AND tagId = ' . $childId)
            ->queryScalar();
        $I->assertSame(1, $pivotCount);

        $childNodeAfter = (new TagGet($elementService))->handle($childRoute);
        $I->assertIsArray($childNodeAfter);
        $contentRoutes = array_column($childNodeAfter['contents'], 'route');
        $I->assertContains($contentRoute, $contentRoutes);
    }
}
