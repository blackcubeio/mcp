<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\File;

use Blackcube\Dcore\Services\FileService;
use Blackcube\Dboard\Components\Rbac;
use Mcp\Schema\Content\AudioContent;
use Mcp\Schema\Content\Content;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;

/**
 * file.fetch - Fetch the binary behind a @blfs/ file reference.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Fetch
{
    public const NAME = 'file.fetch';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_VIEW,
        Rbac::PERMISSION_TAG_VIEW,
    ];

    public function __construct(
        private readonly FileService $fileService,
    ) {}

    public static function description(): string
    {
        return 'Fetch the file behind a @blfs/ reference (the @blfs/ values found in content data and blocs): images come back as image content, audio as audio, anything else as a base64 resource.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ref' => [
                    'type' => 'string',
                    'description' => 'A single @blfs/ file reference, e.g. @blfs/contents/3/about.jpg',
                ],
            ],
            'required' => [
                'ref',
            ],
        ];
    }

    public function handle(string $ref): Content|CallToolResult
    {
        $toolResult = null;
        $isStorageRef = $this->fileService->canHandle($ref) === true && $this->fileService->isTempPath($ref) === false;
        if ($isStorageRef === false) {
            $toolResult = CallToolResult::error([
                new TextContent('Only stored file references can be fetched: ' . $ref),
            ]);
        } elseif ($this->fileService->fileExists($ref) === false) {
            $toolResult = CallToolResult::error([
                new TextContent('File not found: ' . $ref),
            ]);
        } else {
            $binary = $this->fileService->read($ref);
            $mimeType = $this->fileService->mimeType($ref);
            if (str_starts_with($mimeType, 'image/') === true) {
                $toolResult = ImageContent::fromString($binary, $mimeType);
            } elseif (str_starts_with($mimeType, 'audio/') === true) {
                $toolResult = AudioContent::fromString($binary, $mimeType);
            } else {
                $toolResult = EmbeddedResource::fromBlob($ref, base64_encode($binary), $mimeType);
            }
        }
        return $toolResult;
    }
}
