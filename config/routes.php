<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Mcp\Handlers\Mcp;
use Blackcube\Mcp\Handlers\ResourceMetadata;
use Blackcube\Mcp\Middlewares\Authentication;
use Blackcube\Mcp\Middlewares\Locale;
use Blackcube\Mcp\Services\ResourceMetadataFactory;
use Yiisoft\Csrf\CsrfTokenMiddleware;
use Yiisoft\Router\Route;

/** @var array $params */

$endpoint = $params['blackcube/mcp']['endpoint'];

return [
    Route::methods([
        'GET',
        'POST',
        'DELETE',
        'OPTIONS',
    ], $endpoint)
        ->disableMiddleware(CsrfTokenMiddleware::class)
        ->middleware(Locale::class)
        ->middleware(Authentication::class)
        ->action(Mcp::class)
        ->name('mcp'),
    Route::get(ResourceMetadataFactory::METADATA_PATH . $endpoint)
        ->action(ResourceMetadata::class)
        ->name('mcp.resource-metadata'),
];
