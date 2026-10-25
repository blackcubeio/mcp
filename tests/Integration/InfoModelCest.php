<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Services\MapperService;
use Blackcube\Mcp\Services\InfoService;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Blackcube\Mcp\Tests\Support\ServicesCestTrait;

/**
 * Checks the info.model map is complete and honest: every documented entity
 * exposes derived fields carrying a type and a description, and the tool
 * surface matches the real registry (no phantom verbs).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class InfoModelCest
{
    use ServicesCestTrait;

    private function infoService(): InfoService
    {
        return new InfoService(new MapperService($this->makeFileService()));
    }

    public function modelMapIsCompleteAndDerived(IntegrationTester $I): void
    {
        $I->wantTo('check every entity of info.model exposes typed and described fields');

        $model = $this->infoService()->getModel();

        $entityNames = [
            'Content',
            'Tag',
            'Slug',
            'Xeo',
            'Sitemap',
            'Bloc',
            'ElasticSchema',
            'Type',
            'Menu',
            'LlmMenu',
            'Language',
            'Parameter',
            'Author',
        ];
        foreach ($entityNames as $entityName) {
            $I->assertArrayHasKey($entityName, $model, $entityName . ' documented');
            $I->assertNotEmpty($model[$entityName]['role'], $entityName . ' carries a role');
            $I->assertNotEmpty($model[$entityName]['fields'], $entityName . ' exposes derived fields');
            foreach ($model[$entityName]['fields'] as $fieldName => $field) {
                $I->assertNotEmpty($field['type'], $entityName . '.' . $fieldName . ' carries a type');
                $I->assertNotEmpty($field['description'], $entityName . '.' . $fieldName . ' carries a description');
            }
            foreach ($model[$entityName]['relations'] as $relationName => $relation) {
                $I->assertNotEmpty($relation['description'], $entityName . '.' . $relationName . ' carries a description');
            }
        }

        $I->assertNotEmpty($model['Hazeltree']['role']);
        $I->assertNotEmpty($model['Elastic']['role']);
        $I->assertNotEmpty($model['FileReference']['role']);
        $I->assertNotEmpty($model['route']['role']);
    }

    public function toolSurfaceMatchesRegistry(IntegrationTester $I): void
    {
        $I->wantTo('check the tools section reflects the real registry');

        $model = $this->infoService()->getModel();

        $I->assertArrayHasKey('tools', $model);
        $I->assertSame('create, delete, get, list, move, update', $model['tools']['menu']);
        $I->assertSame('create, get, list, move, search, tag, update', $model['tools']['content']);
        $I->assertArrayNotHasKey('Writes', $model, 'the hand-written Writes section is gone');
        $domainsWithoutDelete = [
            'content',
            'tag',
            'author',
            'language',
            'host',
            'type',
            'elasticSchema',
        ];
        foreach ($domainsWithoutDelete as $domain) {
            $I->assertStringNotContainsString('delete', $model['tools'][$domain] ?? '', $domain . ' has no delete verb');
        }
    }
}
