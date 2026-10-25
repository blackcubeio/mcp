<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Content;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractUpdate;

/**
 * content.update - Full update of a content node in a single transaction.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update extends AbstractUpdate
{
    public const NAME = 'content.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_UPDATE,
    ];
    protected const TYPE = 'content';

    protected static function nodeFieldsSchema(): array
    {
        return [
            'name' => [
                'type' => 'string',
            ],
            'typeId' => [
                'type' => 'integer',
            ],
            'languageId' => [
                'type' => 'string',
            ],
            'elasticSchemaId' => [
                'type' => 'integer',
            ],
            'active' => [
                'type' => 'boolean',
            ],
            'dateStart' => [
                'type' => 'string',
                'description' => 'Y-m-d',
            ],
            'dateEnd' => [
                'type' => 'string',
                'description' => 'Y-m-d',
            ],
        ];
    }
}
