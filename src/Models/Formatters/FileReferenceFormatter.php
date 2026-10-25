<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Models\Formatters;

use Blackcube\Dcore\Interfaces\FormatterInterface;

/**
 * MCP file formatter: keeps the raw @blfs/ reference instead of inlining the
 * binary as base64. The agent fetches the file on demand via the file.fetch tool.
 *
 * Substituted for dcore's FileFormatter through the MapperService formatterMap,
 * so the MCP read surface never emits base64 on the formatter path.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class FileReferenceFormatter implements FormatterInterface
{
    public static function load(mixed $fileReference, array $parameters = []): mixed
    {
        return $fileReference;
    }

    public static function save(mixed $fileReference, array $parameters = []): mixed
    {
        return $fileReference;
    }
}
