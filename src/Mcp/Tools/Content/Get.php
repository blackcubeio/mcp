<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Content;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractGet;

/**
 * content.get - Get a content node by its route, via ElementService.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Get extends AbstractGet
{
    public const NAME = 'content.get';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_VIEW,
    ];
    protected const TYPE = 'content';

    protected static function serializedRelations(): array
    {
        return [
            'slug',
            'blocs',
            'tags',
            'authors',
        ];
    }
}
