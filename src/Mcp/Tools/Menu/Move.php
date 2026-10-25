<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Menu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractTreeMove;
use Blackcube\Mcp\Services\MenuService;
use Psr\Log\LoggerInterface;

/**
 * menu.move - Reposition a menu node (and its subtree) in the tree.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Move extends AbstractTreeMove
{
    public const NAME = 'menu.move';
    public const PERMISSIONS = [
        Rbac::PERMISSION_MENU_UPDATE,
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
        return 'Reposition a menu node (with its subtree) relative to another node: mode into = becomes its child, before/after = becomes a sibling. Refused if it would exceed 4 levels. Returns the moved node.';
    }
}
