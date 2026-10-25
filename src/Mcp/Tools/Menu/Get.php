<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Menu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractTreeGet;
use Blackcube\Mcp\Services\MenuService;
use Psr\Log\LoggerInterface;

/**
 * menu.get - Get a navigation menu node by its id.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Get extends AbstractTreeGet
{
    public const NAME = 'menu.get';
    public const PERMISSIONS = [
        Rbac::PERMISSION_MENU_VIEW,
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
        return 'Get a navigation menu node by its id. route is the destination the entry points to; resolve hostId via host.list.';
    }
}
