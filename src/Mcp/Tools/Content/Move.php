<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Content;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractMove;

/**
 * content.move - Reposition a content node (and its subtree) in the tree.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Move extends AbstractMove
{
    public const NAME = 'content.move';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_UPDATE,
    ];
    protected const TYPE = 'content';
}
