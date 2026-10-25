<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dcore\Models\Formatters\FileFormatter;
use Blackcube\Dcore\Services\FileService;
use Blackcube\Dcore\Services\MapperService;
use Blackcube\Mcp\Models\Formatters\FileReferenceFormatter;

/**
 * Export tailored for the MCP read surface, shared by content and tag tools.
 * File fields are returned as their raw @blfs/ reference instead of being
 * inlined as base64 — the binary is fetched on demand, never embedded.
 *
 * Both base64 paths of MapperService are neutralized: the formatter path
 * (FileFormatter on the xeo/author image) through the formatterMap substitution,
 * the inline path (Elastic data files) through the exportFile() override.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ExportService extends MapperService
{
    public function __construct(FileService $fileService)
    {
        parent::__construct($fileService, [
            FileFormatter::class => FileReferenceFormatter::class,
        ]);
    }

    /**
     * Export any model (e.g. a Slug) through its Exportable attributes.
     *
     * @return array<string, mixed>
     */
    public function exportObject(object $model): array
    {
        return $this->exportModel($model);
    }

    protected function exportFile(string $filePath): array|string|null
    {
        return $filePath;
    }
}
