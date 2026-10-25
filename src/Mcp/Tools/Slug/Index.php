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
 * slug.list - List the slugs (URLs), filterable by host and kind.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'slug.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_SLUG_VIEW,
    ];

    public function __construct(
        private readonly SlugService $slugService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'List the slugs (URLs). kind tells what each one carries: element (a Content/Tag, with its route), redirect (targetUrl + httpCode) or orphan. Prefer filtering by kind and/or hostId. Paginated.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'hostId' => [
                    'type' => 'integer',
                    'description' => 'Filter by host id',
                ],
                'kind' => [
                    'type' => 'string',
                    'enum' => [
                        'element',
                        'redirect',
                        'orphan',
                    ],
                    'description' => 'Filter by what the slug carries',
                ],
                'page' => [
                    'type' => 'integer',
                    'description' => 'Page number, 1-based (default 1)',
                ],
                'pageSize' => [
                    'type' => 'integer',
                    'description' => 'Items per page, 1-100 (default 20)',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(?int $hostId = null, ?string $kind = null, int $page = 1, int $pageSize = 20): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->slugService->list($hostId, $kind, $page, $pageSize);
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
