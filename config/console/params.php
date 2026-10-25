<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Mcp\Commands\KeyCreateCommand;
use Blackcube\Mcp\Commands\KeyGcCommand;
use Blackcube\Mcp\Commands\KeyListCommand;
use Blackcube\Mcp\Commands\KeyRevokeCommand;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'mcp:keyCreate' => KeyCreateCommand::class,
            'mcp:keyRevoke' => KeyRevokeCommand::class,
            'mcp:keyList' => KeyListCommand::class,
            'mcp:keyGc' => KeyGcCommand::class,
        ],
    ],
];
