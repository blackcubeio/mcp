<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Slug;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\SlugService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * slug.delete - Delete an autonomous slug (redirect or orphan).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Delete
{
    public const NAME = 'slug.delete';
    public const PERMISSIONS = [
        Rbac::PERMISSION_SLUG_DELETE,
    ];

    public function __construct(
        private readonly SlugService $slugService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Delete an autonomous slug (redirect or orphan) with its xeo/sitemap. Refused on a slug carried by a Content or a Tag: that one is part of a key element, disable it or manage it through content.update/tag.update. Returns the deleted slug.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Slug id',
                ],
            ],
            'required' => [
                'id',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $id): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->slugService->delete($id);
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
