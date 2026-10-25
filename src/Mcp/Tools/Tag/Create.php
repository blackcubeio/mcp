<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Tag;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractCreate;

/**
 * tag.create - Create a bare tag node, placed in the tree (max 2 levels).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create extends AbstractCreate
{
    public const NAME = 'tag.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_TAG_CREATE,
    ];
    protected const TYPE = 'tag';
}
