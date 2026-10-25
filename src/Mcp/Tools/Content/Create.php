<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Content;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractCreate;

/**
 * content.create - Create a bare content node, placed in the tree.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create extends AbstractCreate
{
    public const NAME = 'content.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_CREATE,
    ];
    protected const TYPE = 'content';

    protected static function extraFieldsSchema(): array
    {
        return [
            'dateStart' => [
                'type' => 'string',
                'description' => 'Publication start (Y-m-d)',
            ],
            'dateEnd' => [
                'type' => 'string',
                'description' => 'Publication end (Y-m-d)',
            ],
        ];
    }
}
