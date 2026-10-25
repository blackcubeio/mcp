<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Menu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Mcp\Services\MenuService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * menu.create - Create a navigation menu node, optionally placed in the tree.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'menu.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_MENU_CREATE,
    ];

    public function __construct(
        private readonly MenuService $menuService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create a navigation menu node. Without targetId it becomes a root; with targetId + mode (into = child, before/after = sibling) it is placed relative to that node, capped at 4 levels. route is the destination it points to. Returns the created node.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Menu label',
                ],
                'languageId' => [
                    'type' => 'string',
                    'description' => 'Language (ISO code)',
                ],
                'hostId' => [
                    'type' => 'integer',
                    'description' => 'Host id, or omit for all hosts',
                ],
                'route' => [
                    'type' => 'string',
                    'description' => 'Destination route this entry points to',
                ],
                'queryString' => [
                    'type' => 'string',
                    'description' => 'Optional query string appended to the route',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Active (default true)',
                ],
                'targetId' => [
                    'type' => 'integer',
                    'description' => 'Reference node id for placement; omit for a root',
                ],
                'mode' => [
                    'type' => 'string',
                    'enum' => [
                        TreePosition::Before->value,
                        TreePosition::Into->value,
                        TreePosition::After->value,
                    ],
                    'description' => 'Placement relative to targetId (default into)',
                ],
            ],
            'required' => [
                'name',
                'languageId',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $name, string $languageId, ?int $hostId = null, ?string $route = null, ?string $queryString = null, bool $active = true, ?int $targetId = null, string $mode = TreePosition::Into->value): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->menuService->create($name, $languageId, $hostId, $route, $queryString, $active, $targetId, $mode);
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
