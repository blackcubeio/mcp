<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\Language;
use Blackcube\Mcp\Mcp\Tools\Translation\Link;
use Blackcube\Mcp\Mcp\Tools\Translation\Unlink;
use Blackcube\Mcp\Services\TranslationService;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Round-trips the translation links through the mcp tools against the real
 * DB: link two contents (fr/en) -> group checked in DB -> business guards
 * (same language, mixed types) -> unlink.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TranslationRoundTripCest
{
    use DatabaseCestTrait;

    private function language(string $id, string $name): void
    {
        $language = Language::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($language === null) {
            $language = new Language();
            $language->setId($id);
            $language->setName($name);
            $language->setActive(true);
            $language->save();
        }
    }

    public function testLinkUnlinkRoundTrip(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a translation link through the mcp tools: link -> group -> guards -> unlink, ISO');

        $this->language('fr', 'Français');
        $this->language('en', 'English');

        $french = new Content();
        $french->setName('Page FR');
        $french->setLanguageId('fr');
        $french->save();
        $frenchRoute = 'dcore-c-' . $french->getId();

        $english = new Content();
        $english->setName('Page EN');
        $english->setLanguageId('en');
        $english->save();
        $englishRoute = 'dcore-c-' . $english->getId();

        $group = (new Link(new TranslationService(), new NullLogger()))->handle($frenchRoute, $englishRoute);
        $I->assertNotNull($group['translationGroupId']);
        $I->assertCount(2, $group['translations']);
        $languages = array_column(array_column($group['translations'], 'language'), 'id');
        $I->assertSame([
            'en',
            'fr',
        ], $languages);

        $frenchGroupId = $this->db
            ->createCommand('SELECT translationGroupId FROM contents WHERE id = ' . $french->getId())
            ->queryScalar();
        $englishGroupId = $this->db
            ->createCommand('SELECT translationGroupId FROM contents WHERE id = ' . $english->getId())
            ->queryScalar();
        $I->assertNotNull($frenchGroupId);
        $I->assertSame((int) $frenchGroupId, (int) $englishGroupId, 'both contents share the group');

        $this->language('de', 'Deutsch');
        $german = new Content();
        $german->setName('Seite DE');
        $german->setLanguageId('de');
        $german->save();

        $thirdVariant = (new Link(new TranslationService(), new NullLogger()))->handle($englishRoute, 'dcore-c-' . $german->getId());
        $I->assertNotInstanceOf(CallToolResult::class, $thirdVariant, 'a third language variant joins the group');
        $I->assertCount(3, $thirdVariant['translations']);

        $otherFrench = new Content();
        $otherFrench->setName('Autre FR');
        $otherFrench->setLanguageId('fr');
        $otherFrench->save();

        $duplicate = (new Link(new TranslationService(), new NullLogger()))->handle($englishRoute, 'dcore-c-' . $otherFrench->getId());
        $I->assertInstanceOf(CallToolResult::class, $duplicate, 'duplicated language in the group refused');

        $unlinked = (new Unlink(new TranslationService(), new NullLogger()))->handle($frenchRoute, $englishRoute);
        $remainingLanguages = array_column(array_column($unlinked['translations'], 'language'), 'id');
        $I->assertNotContains('en', $remainingLanguages, 'the english variant left the group');

        $englishGroupId = $this->db
            ->createCommand('SELECT translationGroupId FROM contents WHERE id = ' . $english->getId())
            ->queryScalar();
        $I->assertNull($englishGroupId);
    }
}
