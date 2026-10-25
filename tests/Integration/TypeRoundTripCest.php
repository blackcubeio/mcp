<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dcore\Models\Type;
use Blackcube\Mcp\Mcp\Tools\Type\Create;
use Blackcube\Mcp\Mcp\Tools\Type\Index;
use Blackcube\Mcp\Mcp\Tools\Type\Update;
use Blackcube\Mcp\Services\TypeService;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Real round-trip for the type.* tools: create and update go through the
 * actual tools (save), are checked against the real types / pivot rows, and
 * read back through the list tool — proving mcp output, DB state and re-read
 * agree (ISO). No mock, no stub: real MySQL schema and real ActiveRecord.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TypeRoundTripCest
{
    use DatabaseCestTrait;

    public function testUpdateRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a type through the mcp tools: seed AR -> update tool -> DB -> list, ISO');

        $schema = new ElasticSchema();
        $schema->setName('rt-schema');
        $schema->save();
        $schemaId = $schema->getId();

        $type = new Type();
        $type->setName('rt-type');
        $type->save();
        $typeId = $type->getId();

        $updated = (new Update(new TypeService(), new NullLogger()))->handle($typeId, 'rt-type-renamed', false, true, [
            $schemaId,
        ]);
        $I->assertSame('rt-type-renamed', $updated['name']);
        $I->assertSame(false, $updated['contentAllowed']);
        $I->assertSame(true, $updated['tagAllowed']);
        $I->assertSame([
            [
                'id' => $schemaId,
                'name' => 'rt-schema',
            ],
        ], $updated['elasticSchemas']);

        $name = $this->db
            ->createCommand('SELECT name FROM types WHERE id = ' . $typeId)
            ->queryScalar();
        $I->assertSame('rt-type-renamed', $name);

        $contentAllowed = $this->db
            ->createCommand('SELECT contentAllowed FROM types WHERE id = ' . $typeId)
            ->queryScalar();
        $I->assertSame(0, (int) $contentAllowed);

        $tagAllowed = $this->db
            ->createCommand('SELECT tagAllowed FROM types WHERE id = ' . $typeId)
            ->queryScalar();
        $I->assertSame(1, (int) $tagAllowed);

        $pivotCount = $this->db
            ->createCommand('SELECT COUNT(*) FROM types_elasticSchemas WHERE typeId = ' . $typeId . ' AND elasticSchemaId = ' . $schemaId)
            ->queryScalar();
        $I->assertSame(1, (int) $pivotCount);

        $listed = (new Index(new TypeService()))->handle();
        $expected = [
            'id' => $typeId,
            'name' => 'rt-type-renamed',
            'handler' => null,
            'contentAllowed' => false,
            'tagAllowed' => true,
            'elasticSchemas' => [
                [
                    'id' => $schemaId,
                    'name' => 'rt-schema',
                ],
            ],
        ];
        $I->assertContains($expected, $listed['items']);

        $cleared = (new Update(new TypeService(), new NullLogger()))->handle($typeId, null, null, null, []);
        $I->assertSame([], $cleared['elasticSchemas']);

        $pivotCount = $this->db
            ->createCommand('SELECT COUNT(*) FROM types_elasticSchemas WHERE typeId = ' . $typeId)
            ->queryScalar();
        $I->assertSame(0, (int) $pivotCount);

        $listed = (new Index(new TypeService()))->handle();
        $expected['elasticSchemas'] = [];
        $I->assertContains($expected, $listed['items']);
    }

    public function testCreateRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('create a type through the mcp tool and check the DB row, ISO');

        $schema = new ElasticSchema();
        $schema->setName('ct-schema');
        $schema->save();

        $created = (new Create(new TypeService(), new NullLogger()))->handle('ct-type', 'landing', true, false, [
            $schema->getId(),
        ]);
        $typeId = $created['id'];
        $I->assertSame('ct-type', $created['name']);
        $I->assertSame('landing', $created['handler']);
        $I->assertTrue($created['contentAllowed']);
        $I->assertFalse($created['tagAllowed']);
        $I->assertSame([
            [
                'id' => $schema->getId(),
                'name' => 'ct-schema',
            ],
        ], $created['elasticSchemas']);

        $storedHandler = $this->db
            ->createCommand('SELECT handler FROM types WHERE id = ' . $typeId)
            ->queryScalar();
        $I->assertSame('landing', $storedHandler);

        $pivotCount = $this->db
            ->createCommand('SELECT COUNT(*) FROM types_elasticSchemas WHERE typeId = ' . $typeId)
            ->queryScalar();
        $I->assertSame(1, (int) $pivotCount);

        $unknownSchema = (new Create(new TypeService(), new NullLogger()))->handle('ct-bad', null, true, true, [
            999999,
        ]);
        $I->assertInstanceOf(CallToolResult::class, $unknownSchema, 'unknown schema id refused');
    }
}
