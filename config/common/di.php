<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Mcp\Handlers\Mcp;
use Blackcube\Mcp\Middlewares\Locale;
use Blackcube\Mcp\ServerInfo;
use Blackcube\Mcp\Services\ApiKeyService;
use Blackcube\Mcp\Services\AuthorizationTokenValidator;
use Blackcube\Mcp\Services\ResourceMetadataFactory;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Message\Db\MessageSource;

/** @var array $params */

// The MCP server announces itself under the host application name.
$mcpServerName = $params['blackcube/mcp']['name'] ?? null;
if ($mcpServerName === null) {
    $mcpServerName = $params['application']['name'] ?? 'Blackcube MCP';
}

// Psr\Clock\ClockInterface is expected from the host application (e.g. provided
// by blackcube/oauth2). It is not declared here to avoid a config-plugin
// duplicate-key clash when several packages bind it.
return [
    ClientInterface::class => Client::class,
    AuthorizationTokenValidatorInterface::class => AuthorizationTokenValidator::class,
    ResourceMetadataFactory::class => [
        'class' => ResourceMetadataFactory::class,
        '__construct()' => [
            'endpoint' => $params['blackcube/mcp']['endpoint'],
        ],
    ],
    ApiKeyService::class => [
        'class' => ApiKeyService::class,
        '__construct()' => [
            'maxTtlDays' => $params['blackcube/mcp']['maxTtlDays'],
            'defaultTtlDays' => $params['blackcube/mcp']['defaultTtlDays'],
        ],
    ],
    ServerInfo::class => [
        'class' => ServerInfo::class,
        '__construct()' => [
            'name' => $mcpServerName,
            'version' => $params['blackcube/mcp']['version'] ?? '1.0.0',
        ],
    ],
    Mcp::class => [
        'class' => Mcp::class,
        '__construct()' => [
            'allowedHosts' => $params['blackcube/mcp']['allowedHosts'],
        ],
    ],
    Locale::class => [
        'class' => Locale::class,
        '__construct()' => [
            'locale' => $params['blackcube/mcp']['locale'],
        ],
    ],
    'translation.mcp' => [
        'class' => CategorySource::class,
        '__construct()' => [
            'name' => 'mcp',
            'reader' => DynamicReference::to(MessageSource::class),
        ],
        'tags' => [
            'translation.categorySource',
        ],
    ],
];
