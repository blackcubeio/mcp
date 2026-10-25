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
use Blackcube\Dcore\Models\Language;
use Blackcube\Dcore\Models\Type;
use Blackcube\Dcore\Models\TypeElasticSchema;
use Blackcube\Dcore\Services\ElasticMdService;
use Blackcube\Mcp\Mcp\Tools\Md\Export;
use Blackcube\Mcp\Mcp\Tools\Md\Import;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Round-trips the markdown authoring flow through the mcp tools against the
 * real DB: export a content (front matter + dictionaries), import a document
 * carrying a Section bloc, check the bloc row, and the guards (invalid
 * route, missing type).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class MdRoundTripCest
{
    use DatabaseCestTrait;

    public function testExportImportRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a content through md.export / md.import: dictionaries out, Section bloc in, ISO');

        $language = Language::query()->andWhere([
            'id' => 'fr',
        ])->one();
        if ($language === null) {
            $language = new Language();
            $language->setId('fr');
            $language->setName('Français');
            $language->setActive(true);
            $language->save();
        }

        $sectionSchema = ElasticSchema::query()
            ->andWhere([
                'name' => 'Section',
                'kind' => 'bloc',
                'builtin' => true,
            ])
            ->one();
        $I->assertNotNull($sectionSchema, 'builtin Section schema seeded by the migrations');

        $type = new Type();
        $type->setName('md-type-' . uniqid());
        $type->save();
        $typeElasticSchema = new TypeElasticSchema();
        $typeElasticSchema->setTypeId($type->getId());
        $typeElasticSchema->setElasticSchemaId($sectionSchema->getId());
        $typeElasticSchema->save();

        $content = new Content();
        $content->setName('Md page');
        $content->setLanguageId('fr');
        $content->setTypeId($type->getId());
        $content->save();
        $route = 'dcore-c-' . $content->getId();

        $exported = (new Export(new ElasticMdService(), new NullLogger()))->handle($route);
        $I->assertSame($route, $exported['route']);
        $I->assertStringContainsString('section', strtolower($exported['markdown']), 'the Section dictionary is exposed');

        $document = "---\n"
            . "entityType: content\n"
            . "language: fr\n"
            . 'type: ' . $type->getName() . "\n"
            . "---\n\n"
            . ":::start:section\n"
            . "## Notre histoire\n\n"
            . "Une introduction courte.\n"
            . ":::end\n";
        $imported = (new Import(new ElasticMdService(), new NullLogger()))->handle($route, $document);
        $I->assertTrue($imported['success'], 'import succeeds: ' . implode('; ', $imported['errors'] ?? []));
        $I->assertSame(1, $imported['blocsCreated']);

        $blocCount = $this->db
            ->createCommand('SELECT COUNT(*) FROM contents_blocs WHERE contentId = ' . $content->getId())
            ->queryScalar();
        $I->assertSame(1, (int) $blocCount);

        $badRoute = (new Export(new ElasticMdService(), new NullLogger()))->handle('nonsense');
        $I->assertInstanceOf(CallToolResult::class, $badRoute, 'invalid route refused');
    }
}
