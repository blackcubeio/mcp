<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Menu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\MenuService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * menu.update - Update a menu node's fields (not its position; use menu.move).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'menu.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_MENU_UPDATE,
    ];

    public function __construct(
        private readonly MenuService $menuService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update a menu node by id: its label, language, host, route, queryString and active flag. Only the provided fields change. Use menu.move to reposition it in the tree. Returns the updated node.';
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
                    'description' => 'Menu id to update',
                ],
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
                    'description' => 'Host id',
                ],
                'route' => [
                    'type' => 'string',
                    'description' => 'Destination route',
                ],
                'queryString' => [
                    'type' => 'string',
                    'description' => 'Query string appended to the route',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Active',
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
    public function handle(int $id, ?string $name = null, ?string $languageId = null, ?int $hostId = null, ?string $route = null, ?string $queryString = null, ?bool $active = null): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->menuService->update($id, $name, $languageId, $hostId, $route, $queryString, $active);
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
