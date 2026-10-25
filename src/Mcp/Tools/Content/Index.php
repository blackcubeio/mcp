<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Content;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractIndex;

/**
 * content.list - List content nodes of the tree via Hazeltree scopes, paginated.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index extends AbstractIndex
{
    public const NAME = 'content.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_VIEW,
    ];
    protected const TYPE = 'content';
}
