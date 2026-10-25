<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Tag;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractUpdate;

/**
 * tag.update - Full update of a tag node in a single transaction.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update extends AbstractUpdate
{
    public const NAME = 'tag.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_TAG_UPDATE,
    ];
    protected const TYPE = 'tag';

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
        ];
    }
}
