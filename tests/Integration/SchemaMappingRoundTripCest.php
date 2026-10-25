<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Mcp\Mcp\Tools\SchemaMapping\Create;
use Blackcube\Mcp\Mcp\Tools\SchemaMapping\Delete;
use Blackcube\Mcp\Mcp\Tools\SchemaMapping\Index;
use Blackcube\Mcp\Mcp\Tools\SchemaMapping\Update;
use Blackcube\Mcp\Services\SchemaMappingService;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Round-trips a SchemaSchema mapping through the mcp tools against the real
 * DB: create -> list -> update -> delete, with the guards (duplicate pair,
 * invalid JSON).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class SchemaMappingRoundTripCest
{
    use DatabaseCestTrait;

    public function testCreateUpdateDeleteRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a schema mapping through the mcp tools: create -> list -> update -> delete, ISO');

        $regular = new ElasticSchema();
        $regular->setName('sm-regular-' . uniqid());
        $regular->setSchema('{"type":"object"}');
        $regular->save();

        $xeo = new ElasticSchema();
        $xeo->setName('sm-xeo-' . uniqid());
        $xeo->setSchema('{"type":"object"}');
        $xeo->save();

        $regularId = $regular->getId();
        $xeoId = $xeo->getId();

        $created = (new Create(new SchemaMappingService(), new NullLogger()))->handle($regularId, $xeoId, '{"mapping":{"title":"name"}}');
        $I->assertSame($regularId, $created['regularSchema']['id']);
        $I->assertSame($xeoId, $created['xeoSchema']['id']);
        $I->assertSame([
            'mapping' => [
                'title' => 'name',
            ],
        ], $created['mapping']);

        $storedCount = $this->db
            ->createCommand('SELECT COUNT(*) FROM schemas_schemas WHERE regularElasticSchemaId = ' . $regularId . ' AND xeoElasticSchemaId = ' . $xeoId)
            ->queryScalar();
        $I->assertSame(1, (int) $storedCount);

        $duplicate = (new Create(new SchemaMappingService(), new NullLogger()))->handle($regularId, $xeoId, '{"mapping":{}}');
        $I->assertInstanceOf(CallToolResult::class, $duplicate, 'duplicate pair refused');

        $badJson = (new Update(new SchemaMappingService(), new NullLogger()))->handle($regularId, $xeoId, '{not json');
        $I->assertInstanceOf(CallToolResult::class, $badJson, 'invalid JSON refused');

        $updated = (new Update(new SchemaMappingService(), new NullLogger()))->handle($regularId, $xeoId, '{"mapping":{"title":"name","description":"caption"}}');
        $I->assertSame('caption', $updated['mapping']['mapping']['description']);

        $listed = (new Index(new SchemaMappingService()))->handle();
        $listedPairs = [];
        foreach ($listed['items'] as $listedMapping) {
            $listedPairs[] = $listedMapping['regularSchema']['id'] . '-' . $listedMapping['xeoSchema']['id'];
        }
        $I->assertContains($regularId . '-' . $xeoId, $listedPairs);

        $deleted = (new Delete(new SchemaMappingService(), new NullLogger()))->handle($regularId, $xeoId);
        $I->assertSame($regularId, $deleted['regularSchema']['id']);

        $remaining = $this->db
            ->createCommand('SELECT COUNT(*) FROM schemas_schemas WHERE regularElasticSchemaId = ' . $regularId . ' AND xeoElasticSchemaId = ' . $xeoId)
            ->queryScalar();
        $I->assertSame(0, (int) $remaining);
    }
}
