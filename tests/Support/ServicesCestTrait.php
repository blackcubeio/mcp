<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Support;

use Blackcube\Dcore\Models\Type;
use Blackcube\Dcore\Models\TypeElasticSchema;
use Blackcube\Dcore\Services\FileService;
use Blackcube\Dcore\Services\XeoRefreshService;
use Blackcube\FileProvider\FileProvider;
use Blackcube\Mcp\Services\ElementService;
use Blackcube\Mcp\Services\ExportService;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Yiisoft\Aliases\Aliases;

/**
 * Shared wiring for the content/tag/file round-trip Cests: the real dcore
 * services (ElementService over ExportService + FileService, on a real local
 * FileProvider) and the prerequisite seeding the fresh schema needs. No mock.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait ServicesCestTrait
{
    /**
     * Local @blfs/@bltmp storage root for the file tests (under the codeception
     * output, gitignored). The physical files land here.
     */
    protected function fileDataRoot(): string
    {
        return dirname(__DIR__) . '/_output/filedata';
    }

    /**
     * A real FileService backed by two local filesystems (@blfs and @bltmp),
     * exactly as the running app wires them, so the @bltmp -> @blfs move is real.
     */
    protected function makeFileService(): FileService
    {
        $aliases = new Aliases([
            '@data' => $this->fileDataRoot(),
        ]);
        $fileProvider = new FileProvider($aliases, [
            FileProvider::ALIAS_FS => [
                'type' => 'local',
                'path' => '@data/blfs',
            ],
            FileProvider::ALIAS_TMP => [
                'type' => 'local',
                'path' => '@data/tmp',
            ],
        ]);

        return new FileService($fileProvider);
    }

    protected function makeElementService(FileService $fileService): ElementService
    {
        return new ElementService(new ExportService($fileService), $fileService, new XeoRefreshService($fileService));
    }

    /**
     * Wipe the physical file storage so a file test starts from empty disk.
     */
    protected function resetFileStorage(): void
    {
        $root = $this->fileDataRoot();
        if (is_dir($root) === true) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $storedFile) {
                if ($storedFile->isDir() === true) {
                    rmdir($storedFile->getPathname());
                } else {
                    unlink($storedFile->getPathname());
                }
            }
            rmdir($root);
        }
    }

    /**
     * Seed the prerequisites the fresh (drop+migrate) schema needs. The dcore
     * migrations already seed the languages (incl. fr), the default host and the
     * builtin ElasticSchemas (a page schema + an Image bloc with image/alt); only
     * the type is created, plus its pivots to the page and Image-bloc schemas.
     *
     * @return array{languageId: string, hostId: int, typeId: int, pageSchemaId: int, imageBlocSchemaId: int}
     */
    protected function seedBaseData(): array
    {
        $hostId = (int) $this->db
            ->createCommand('SELECT id FROM hosts ORDER BY id LIMIT 1')
            ->queryScalar();

        $type = new Type();
        $type->setName('Page');
        $type->setContentAllowed(true);
        $type->setTagAllowed(true);
        $type->save();

        $pageSchemaId = (int) $this->db
            ->createCommand("SELECT id FROM elasticSchemas WHERE kind = 'page' ORDER BY id LIMIT 1")
            ->queryScalar();
        $imageBlocSchemaId = (int) $this->db
            ->createCommand("SELECT id FROM elasticSchemas WHERE name = 'Image' AND kind = 'bloc' LIMIT 1")
            ->queryScalar();

        foreach ([
            $pageSchemaId,
            $imageBlocSchemaId,
        ] as $schemaId) {
            $pivot = new TypeElasticSchema();
            $pivot->setTypeId($type->getId());
            $pivot->setElasticSchemaId($schemaId);
            $pivot->save();
        }

        return [
            'languageId' => 'fr',
            'hostId' => $hostId,
            'typeId' => $type->getId(),
            'pageSchemaId' => $pageSchemaId,
            'imageBlocSchemaId' => $imageBlocSchemaId,
        ];
    }
}
