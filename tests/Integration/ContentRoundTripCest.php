<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Helpers\Element;
use Blackcube\Dcore\Models\Xeo;
use Blackcube\Mcp\Mcp\Tools\Content\Create;
use Blackcube\Mcp\Mcp\Tools\Content\Get;
use Blackcube\Mcp\Mcp\Tools\Content\Move;
use Blackcube\Mcp\Mcp\Tools\Content\Update;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Blackcube\Mcp\Tests\Support\ServicesCestTrait;
use Psr\Log\NullLogger;

/**
 * Real round-trip for the content.* tools: each write goes through the actual
 * tool (the real ElementService transaction), is checked against the real
 * database rows (contents/slugs/xeos/sitemaps), and read back through the read
 * tool — proving mcp output, DB state and re-read agree (ISO). No mock, no stub.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ContentRoundTripCest
{
    use DatabaseCestTrait;
    use ServicesCestTrait;

    public function testCreateUpdateMoveRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a content through the mcp tools: create -> update (slug/xeo/sitemap) -> move, DB + read ISO');

        $base = $this->seedBaseData();
        $elementService = $this->makeElementService($this->makeFileService());

        $created = (new Create($elementService, new NullLogger()))->handle('Home', $base['typeId'], $base['languageId']);
        $I->assertIsArray($created);
        $I->assertArrayHasKey('route', $created);
        $routeA = $created['route'];
        $idA = Element::createFromRoute($routeA)->getId();

        $storedContent = $this->db
            ->createCommand('SELECT name, languageId, typeId, level, `active`+0 AS active FROM contents WHERE id = ' . $idA)
            ->queryOne();
        $I->assertSame('Home', $storedContent['name']);
        $I->assertSame('fr', $storedContent['languageId']);
        $I->assertSame((int) $base['typeId'], (int) $storedContent['typeId']);
        $I->assertSame(1, (int) $storedContent['level']);
        $I->assertSame(0, (int) $storedContent['active']);

        $got = (new Get($elementService))->handle($routeA);
        $I->assertIsArray($got);
        $I->assertSame($routeA, $got['route']);
        $I->assertSame('Home', $got['name']);

        $updated = (new Update($elementService, new NullLogger()))->handle(
            $routeA,
            [
                'name' => 'Home updated',
            ],
            null,
            [
                'hostId' => $base['hostId'],
                'path' => 'home',
                'active' => true,
            ],
            [
                'frequency' => 'weekly',
                'priority' => 0.8,
                'active' => true,
            ],
            [
                'title' => 'Home title',
                'description' => 'Home description',
                'canonical' => true,
                'noindex' => true,
                'og' => true,
                'ogType' => 'website',
                'twitter' => true,
                'twitterCard' => 'summary',
                'active' => true,
            ],
        );
        $I->assertIsArray($updated);

        $name = $this->db->createCommand('SELECT name FROM contents WHERE id = ' . $idA)->queryScalar();
        $I->assertSame('Home updated', $name);

        $slugId = (int) $this->db->createCommand('SELECT slugId FROM contents WHERE id = ' . $idA)->queryScalar();
        $I->assertGreaterThan(0, $slugId);

        $slug = $this->db->createCommand('SELECT hostId, path FROM slugs WHERE id = ' . $slugId)->queryOne();
        $I->assertSame((int) $base['hostId'], (int) $slug['hostId']);
        $I->assertSame('home', $slug['path']);

        $xeo = $this->db->createCommand('SELECT title, description FROM xeos WHERE slugId = ' . $slugId)->queryOne();
        $I->assertSame('Home title', $xeo['title']);
        $I->assertSame('Home description', $xeo['description']);

        $storedXeo = Xeo::query()->andWhere([
            'slugId' => $slugId,
        ])->one();
        $I->assertInstanceOf(Xeo::class, $storedXeo);
        $I->assertSame($slugId, $storedXeo->getCanonicalSlugId());
        $I->assertTrue($storedXeo->isNoindex());
        $I->assertTrue($storedXeo->isOg());
        $I->assertSame('website', $storedXeo->getOgType());
        $I->assertTrue($storedXeo->isTwitter());
        $I->assertSame('summary', $storedXeo->getTwitterCard());

        $sitemap = $this->db
            ->createCommand('SELECT frequency, `priority`+0 AS priority FROM sitemaps WHERE slugId = ' . $slugId)
            ->queryOne();
        $I->assertSame('weekly', $sitemap['frequency']);
        $I->assertEqualsWithDelta(0.8, (float) $sitemap['priority'], 0.0001);

        $got2 = (new Get($elementService))->handle($routeA);
        $I->assertSame('Home updated', $got2['name']);
        $I->assertIsArray($got2['slug']);
        $I->assertSame('home', $got2['slug']['path']);

        $parent = (new Create($elementService, new NullLogger()))->handle('Section', $base['typeId'], $base['languageId']);
        $routeB = $parent['route'];
        $idB = Element::createFromRoute($routeB)->getId();

        $moved = (new Move($elementService, new NullLogger()))->handle($routeA, $routeB, 'into');
        $I->assertIsArray($moved);
        $I->assertSame(2, (int) $moved['hazeltree']['level']);

        $level = (int) $this->db->createCommand('SELECT level FROM contents WHERE id = ' . $idA)->queryScalar();
        $I->assertSame(2, $level);

        $parentBounds = $this->db->createCommand('SELECT `left`, `right` FROM contents WHERE id = ' . $idB)->queryOne();
        $childBounds = $this->db->createCommand('SELECT `left`, `right` FROM contents WHERE id = ' . $idA)->queryOne();
        $I->assertGreaterThan((float) $parentBounds['left'], (float) $childBounds['left']);
        $I->assertLessThan((float) $parentBounds['right'], (float) $childBounds['right']);
    }
}
