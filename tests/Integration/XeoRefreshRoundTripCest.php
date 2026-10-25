<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dcore\Models\SchemaSchema;
use Blackcube\Dcore\Models\Slug;
use Blackcube\Dcore\Models\Xeo;
use Blackcube\Mcp\Mcp\Tools\Xeo\Refresh;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Blackcube\Mcp\Tests\Support\ServicesCestTrait;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Round-trips the xeo refresh through the mcp tool against the real DB: a
 * content with a page schema and a mapping towards a xeo schema gets its xeo
 * blocs rebuilt (created once, replaced on re-run, not accumulated), with
 * the guard on elements carrying no xeo.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class XeoRefreshRoundTripCest
{
    use DatabaseCestTrait;
    use ServicesCestTrait;

    private function refreshTool(): Refresh
    {
        return new Refresh($this->makeElementService($this->makeFileService()), new NullLogger());
    }

    public function testRefreshRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('rebuild the xeo blocs of a content through xeo.refresh, replacement checked in DB, ISO');

        $pageSchema = new ElasticSchema();
        $pageSchema->setName('xr-page-' . uniqid());
        $pageSchema->setSchema('{"type":"object","properties":{"title":{"type":"string"}}}');
        $pageSchema->save();

        $xeoSchema = new ElasticSchema();
        $xeoSchema->setName('xr-xeo-' . uniqid());
        $xeoSchema->setSchema('{"type":"object","properties":{"name":{"type":"string"}}}');
        $xeoSchema->save();

        $schemaSchema = new SchemaSchema();
        $schemaSchema->setRegularElasticSchemaId($pageSchema->getId());
        $schemaSchema->setXeoElasticSchemaId($xeoSchema->getId());
        $schemaSchema->setMapping('{"mapping":{"title":"name"}}');
        $schemaSchema->save();

        $slug = new Slug();
        $slug->setPath('xeo-refresh-' . uniqid());
        $slug->save();

        $xeo = new Xeo();
        $xeo->setSlugId($slug->getId());
        $xeo->save();
        $xeoId = $xeo->getId();

        $content = new Content();
        $content->setName('Xr page');
        $content->setSlugId($slug->getId());
        $content->setElasticSchemaId($pageSchema->getId());
        $content->title = 'Mon titre';
        $content->save();
        $route = 'dcore-c-' . $content->getId();

        $refreshed = $this->refreshTool()->handle($route);
        $I->assertSame(1, $refreshed['blocsCreated']);

        $xeoBlocCount = $this->db
            ->createCommand('SELECT COUNT(*) FROM xeos_blocs WHERE xeoId = ' . $xeoId)
            ->queryScalar();
        $I->assertSame(1, (int) $xeoBlocCount);

        $freshXeo = Xeo::query()->andWhere([
            'id' => $xeoId,
        ])->one();
        $createdXeoBlocs = [];
        foreach ($freshXeo->getBlocsQuery()->each() as $createdXeoBloc) {
            $createdXeoBlocs[] = $createdXeoBloc;
        }
        $I->assertCount(1, $createdXeoBlocs);
        $I->assertSame('Mon titre', $createdXeoBlocs[0]->name, 'the field map prefilled name from title, read through the elastic API');

        $reRefreshed = $this->refreshTool()->handle($route);
        $I->assertSame(1, $reRefreshed['blocsCreated']);
        $xeoBlocCount = $this->db
            ->createCommand('SELECT COUNT(*) FROM xeos_blocs WHERE xeoId = ' . $xeoId)
            ->queryScalar();
        $I->assertSame(1, (int) $xeoBlocCount, 'replaced, not accumulated');

        $orphan = new Content();
        $orphan->setName('No xeo');
        $orphan->save();
        $noXeo = $this->refreshTool()->handle('dcore-c-' . $orphan->getId());
        $I->assertInstanceOf(CallToolResult::class, $noXeo, 'element without xeo refused with guidance');
    }
}
