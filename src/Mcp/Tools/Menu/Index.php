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

/**
 * menu.list - List navigation menu nodes in tree order.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'menu.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_MENU_VIEW,
    ];

    public function __construct(
        private readonly MenuService $menuService,
    ) {}

    public static function description(): string
    {
        return 'List navigation menu nodes (a tree capped at 4 levels), in tree order. Optionally filtered by languageId and/or hostId. Each item carries its level for the hierarchy; route is the destination the entry points to (resolve hostId via host.list).';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'languageId' => [
                    'type' => 'string',
                    'description' => 'Filter by language (ISO code)',
                ],
                'hostId' => [
                    'type' => 'integer',
                    'description' => 'Filter by host id',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(?string $languageId = null, ?int $hostId = null): array
    {
        return $this->menuService->list($languageId, $hostId);
    }
}
