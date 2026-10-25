<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Commons;

use Blackcube\Mcp\Enums\ContentTreeMode;
use Blackcube\Mcp\Services\ElementService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;

/**
 * Lists element nodes of the tree via Hazeltree scopes, paginated, shared by
 * content and tag. A concrete tool sets only NAME and TYPE; the depth limit
 * comes from ElementService::MAX_LEVEL.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractIndex
{
    protected const TYPE = '';

    public function __construct(
        protected ElementService $elementService,
    ) {}

    public static function description(): string
    {
        return 'List ' . static::TYPE . ' nodes via tree scopes (roots, children, descendants, parent, ancestors, '
            . 'siblings). Without a route lists the roots; the relative modes require a route. The ' . static::TYPE
            . ' tree is capped at ' . ElementService::MAX_LEVEL[static::TYPE] . ' levels. Paginated.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'route' => [
                    'type' => 'string',
                    'description' => 'Reference ' . static::TYPE . ' route (dcore-XXX) for relative modes',
                ],
                'mode' => [
                    'type' => 'string',
                    'enum' => ContentTreeMode::values(),
                    'description' => 'Tree scope (default: roots without a route, children with one)',
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
    public function handle(?string $route = null, ?string $mode = null, int $page = 1, int $pageSize = 20): array|CallToolResult
    {
        $toolResult = null;
        $treeMode = $mode !== null ? ContentTreeMode::tryFrom($mode) : null;
        if ($mode !== null && $treeMode === null) {
            $toolResult = CallToolResult::error([
                new TextContent(
                    'Invalid mode ' . $mode . ' (allowed: ' . implode(', ', ContentTreeMode::values()) . ')',
                ),
            ]);
        } else {
            $elementPage = $this->elementService->list(static::TYPE, $treeMode, $route, $page, $pageSize);
            if ($elementPage === null) {
                $toolResult = CallToolResult::error([
                    new TextContent('Unresolvable ' . static::TYPE . ' route for this mode: ' . ($route ?? '(none)')),
                ]);
            } else {
                $toolResult = $elementPage;
            }
        }

        return $toolResult;
    }
}
