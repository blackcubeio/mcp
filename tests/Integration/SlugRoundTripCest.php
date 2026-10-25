<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\Slug;
use Blackcube\Mcp\Mcp\Tools\Slug\Create;
use Blackcube\Mcp\Mcp\Tools\Slug\Delete;
use Blackcube\Mcp\Mcp\Tools\Slug\Get;
use Blackcube\Mcp\Mcp\Tools\Slug\Index;
use Blackcube\Mcp\Mcp\Tools\Slug\Update;
use Blackcube\Mcp\Services\SlugService;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Round-trips the slugs through the mcp tools against the real DB: create a
 * redirect -> list/get (kinds resolved) -> update -> delete, with the key
 * element guards (an element slug never becomes a redirect, never deleted).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class SlugRoundTripCest
{
    use DatabaseCestTrait;

    public function testRedirectRoundTripAndElementGuards(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a redirect slug through the mcp tools and check the element slug guards, ISO');

        $redirect = (new Create(new SlugService(), new NullLogger()))->handle('old-page', 'https://www.blackcube.io/new-page', 301);
        $redirectId = $redirect['id'];
        $I->assertSame('redirect', $redirect['kind']);
        $I->assertSame('https://www.blackcube.io/new-page', $redirect['targetUrl']);
        $I->assertSame(301, $redirect['httpCode']);

        $storedTarget = $this->db
            ->createCommand('SELECT targetUrl FROM slugs WHERE id = ' . $redirectId)
            ->queryScalar();
        $I->assertSame('https://www.blackcube.io/new-page', $storedTarget);

        $duplicate = (new Create(new SlugService(), new NullLogger()))->handle('old-page', 'https://www.blackcube.io/other', 301);
        $I->assertInstanceOf(CallToolResult::class, $duplicate, 'duplicate path refused on the same host');

        $elementSlug = new Slug();
        $elementSlug->setPath('carried-page-' . uniqid());
        $elementSlug->save();
        $content = new Content();
        $content->setName('slug-carrier');
        $content->setSlugId($elementSlug->getId());
        $content->save();
        $elementSlugId = $elementSlug->getId();

        $fetched = (new Get(new SlugService(), new NullLogger()))->handle($elementSlugId);
        $I->assertSame('element', $fetched['kind']);
        $I->assertSame('dcore-c-' . $content->getId(), $fetched['element']['route']);
        $I->assertSame('slug-carrier', $fetched['element']['name']);

        $listedRedirects = (new Index(new SlugService(), new NullLogger()))->handle(null, 'redirect');
        $listedIds = array_column($listedRedirects['items'], 'id');
        $I->assertContains($redirectId, $listedIds);
        $I->assertNotContains($elementSlugId, $listedIds);

        $listedElements = (new Index(new SlugService(), new NullLogger()))->handle(null, 'element');
        $listedIds = array_column($listedElements['items'], 'id');
        $I->assertContains($elementSlugId, $listedIds);
        $I->assertNotContains($redirectId, $listedIds);

        $updated = (new Update(new SlugService(), new NullLogger()))->handle($redirectId, null, null, 302);
        $I->assertSame(302, $updated['httpCode']);

        $refusedRedirect = (new Update(new SlugService(), new NullLogger()))->handle($elementSlugId, null, 'https://www.blackcube.io/hijack');
        $I->assertInstanceOf(CallToolResult::class, $refusedRedirect, 'an element slug cannot become a redirect');

        $disabled = (new Update(new SlugService(), new NullLogger()))->handle($elementSlugId, null, null, null, null, false);
        $I->assertFalse($disabled['active'], 'an element slug can be disabled');

        $refusedDelete = (new Delete(new SlugService(), new NullLogger()))->handle($elementSlugId);
        $I->assertInstanceOf(CallToolResult::class, $refusedDelete, 'an element slug is never deleted');

        $deleted = (new Delete(new SlugService(), new NullLogger()))->handle($redirectId);
        $I->assertSame('old-page', $deleted['path']);

        $remaining = $this->db
            ->createCommand('SELECT COUNT(*) FROM slugs WHERE id = ' . $redirectId)
            ->queryScalar();
        $I->assertSame(0, (int) $remaining);
    }
}
