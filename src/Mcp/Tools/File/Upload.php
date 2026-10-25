<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\File;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\FileUploadService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * file.upload - Store a file in the temporary area and return its @bltmp/ reference.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Upload
{
    public const NAME = 'file.upload';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_UPDATE,
        Rbac::PERMISSION_TAG_UPDATE,
    ];

    public function __construct(
        private readonly FileUploadService $fileUploadService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Upload a file and get a temporary @bltmp/ reference. Provide the bytes as base64 (content, optionally a data: URI) or a url the server downloads. The returned ref is then used as an image/file field value in content.update (a bloc data field, or xeo.image); the save moves it to its permanent @blfs/ location with the right ids.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'filename' => [
                    'type' => 'string',
                    'description' => 'File name with extension, e.g. hero.png',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'File bytes as base64 (or a data: URI). Use this or url.',
                ],
                'url' => [
                    'type' => 'string',
                    'description' => 'URL the server downloads the file from. Use this or content.',
                ],
            ],
            'required' => [
                'filename',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $filename, ?string $content = null, ?string $url = null): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->fileUploadService->upload($filename, $content, $url);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
            ]);
            $toolResult = CallToolResult::error([
                new TextContent($e->getMessage()),
            ]);
        }

        return $toolResult;
    }
}
