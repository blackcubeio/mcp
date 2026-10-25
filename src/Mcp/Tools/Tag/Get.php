<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Tag;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractGet;

/**
 * tag.get - Get a tag node by its route, via ElementService.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Get extends AbstractGet
{
    public const NAME = 'tag.get';
    public const PERMISSIONS = [
        Rbac::PERMISSION_TAG_VIEW,
    ];
    protected const TYPE = 'tag';

    protected static function serializedRelations(): array
    {
        return [
            'slug',
            'blocs',
            'authors',
        ];
    }
}
