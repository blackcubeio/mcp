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
use Blackcube\Mcp\Mcp\Tools\Content\Update as ContentUpdate;
use Blackcube\Mcp\Mcp\Tools\File\Fetch;
use Blackcube\Mcp\Mcp\Tools\File\Upload;
use Blackcube\Mcp\Services\FileUploadService;
use Blackcube\Mcp\Tests\Support\DatabaseCestTrait;
use Blackcube\Mcp\Tests\Support\IntegrationTester;
use Blackcube\Mcp\Tests\Support\ServicesCestTrait;
use GuzzleHttp\Client;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\NullLogger;

/**
 * Real round-trip for the file.* tools and the FileService move (the refactor):
 * file.upload lands the bytes in @bltmp; content.update with an Image bloc moves
 * the file to its permanent @blfs/contents/<id>/blocs/<blocId>/ location (real
 * disk), the returned ref switches from @bltmp to @blfs, and file.fetch reads it
 * back as image content. DB + disk verified, no mock.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class FileRoundTripCest
{
    use DatabaseCestTrait;
    use ServicesCestTrait;

    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC';

    public function testUploadThenStoreThroughBlocThenFetch(IntegrationTester $I): void
    {
        $I->wantTo('round-trip a file: upload to @bltmp, store via a bloc save (move to @blfs), fetch it back');

        $this->resetFileStorage();
        $base = $this->seedBaseData();
        $fileService = $this->makeFileService();
        $elementService = $this->makeElementService($fileService);

        $uploaded = (new Upload(new FileUploadService($fileService, new Client()), new NullLogger()))->handle('hero.png', self::PNG_BASE64);
        $I->assertIsArray($uploaded);
        $tempRef = $uploaded['ref'];
        $I->assertStringStartsWith('@bltmp/', $tempRef);
        $I->assertTrue($fileService->fileExists($tempRef));

        $content = (new ContentCreate($elementService, new NullLogger()))->handle('Gallery', $base['typeId'], $base['languageId']);
        $contentRoute = $content['route'];
        $contentId = Element::createFromRoute($contentRoute)->getId();

        $updated = (new ContentUpdate($elementService, new NullLogger()))->handle(
            $contentRoute,
            null,
            null,
            null,
            null,
            null,
            [
                [
                    'elasticSchemaId' => $base['imageBlocSchemaId'],
                    'data' => [
                        'image' => $tempRef,
                        'alt' => 'A hero',
                    ],
                ],
            ],
        );
        $I->assertIsArray($updated);
        $I->assertArrayHasKey('blocs', $updated);
        $I->assertCount(1, $updated['blocs']);

        $blocId = (int) $updated['blocs'][0]['id'];
        $storedRef = $updated['blocs'][0]['data']['image'];
        $I->assertStringStartsWith('@blfs/', $storedRef);
        $I->assertSame('@blfs/contents/' . $contentId . '/blocs/' . $blocId . '/hero.png', $storedRef);

        $I->assertTrue($fileService->fileExists($storedRef));
        $I->assertFileExists($this->fileDataRoot() . '/blfs/contents/' . $contentId . '/blocs/' . $blocId . '/hero.png');

        $I->assertFalse($fileService->fileExists($tempRef));

        $fetched = (new Fetch($fileService))->handle($storedRef);
        $I->assertNotInstanceOf(CallToolResult::class, $fetched);
        $I->assertInstanceOf(ImageContent::class, $fetched);
    }
}
