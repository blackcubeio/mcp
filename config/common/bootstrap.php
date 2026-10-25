<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Psr\Container\ContainerInterface;
use Yiisoft\Aliases\Aliases;

return [
    static function (ContainerInterface $container): void {
        if ($container->has(Aliases::class) === true) {
            $container->get(Aliases::class)->set('@mcp', dirname(__DIR__, 2) . '/src');
        }
    },
];
