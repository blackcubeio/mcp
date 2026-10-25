<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Menu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractTreeDelete;
use Blackcube\Mcp\Services\MenuService;
use Psr\Log\LoggerInterface;

/**
 * menu.delete - Delete a leaf menu node (refused if it has children).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Delete extends AbstractTreeDelete
{
    public const NAME = 'menu.delete';
    public const PERMISSIONS = [
        Rbac::PERMISSION_MENU_DELETE,
    ];
    protected const TYPE = 'menu';

    public function __construct(
        MenuService $menuService,
        LoggerInterface $logger,
    ) {
        parent::__construct(
            treeService: $menuService,
            logger: $logger,
        );
    }

    public static function description(): string
    {
        return 'Delete a menu node by id. Refused if it still has child nodes (move or delete them first — children are never deleted automatically). Returns the deleted node.';
    }
}
