# Blackcube MCP

MCP server exposing the content administration operations of the Blackcube ecosystem, on top of [`blackcube/dcore`](https://github.com/blackcubeio/dcore). Opaque MCP keys or OAuth2 access tokens issued by the dboard authorization server, tools gated on dboard permissions, Streamable HTTP transport mounted as a PSR-15 middleware.

You generate an MCP key, or let an LLM client register itself and ask an administrator for consent; then the agent drives the content tree, blocs, taxonomy and discoverability through `dcore` — no parallel data path, no business logic duplicated.

## Authentication

Two credentials open the server, both carrying dboard permissions:

- an opaque MCP key, generated in the dboard "MCP keys" screen or in the console, for the agents you run yourself;
- an OAuth2 access token issued by the dboard authorization server, for LLM clients: the client registers itself (RFC 7591), sends the administrator to the consent screen (authorization code + PKCE), and receives a token bounded by the permissions that administrator holds. The bound is checked again on every request: a right lost in dboard is lost on the server at once.

The server publishes its protected resource metadata (RFC 9728) under `/.well-known/oauth-protected-resource` followed by the endpoint path, and points at `/dboard/oauth/mcp`, its population on the dboard side. Each tool declares the dboard permissions it gates on (`PERMISSIONS`), and `MCP:CONNECT` is required for any credential.

[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[![Packagist Version](https://img.shields.io/packagist/v/blackcube/mcp.svg)](https://packagist.org/packages/blackcube/mcp)

## Quickstart

```bash
composer require blackcube/mcp
```

The package is a dboard addon. Its DI, routes and parameters are merged by `yiisoft/config`; the host application declares the addon and its migrations in its parameters:

```php
use Blackcube\Mcp\Dboard\McpAddon;

return [
    'yiisoft/db-migration' => [
        'sourceNamespaces' => [
            'Blackcube\\Dboard\\Migrations',
            'Blackcube\\Dcore\\Migrations',
            'Blackcube\\Mcp\\Migrations',
        ],
    ],
    'blackcube/dboard' => [
        'addons' => [
            'mcp' => [
                'rbac' => [McpAddon::class, 'getRbac'],
                'sidebar' => [McpAddon::class, 'getSidebar'],
                'routes' => [McpAddon::class, 'getRoutesGroup'],
                'population' => [McpAddon::class, 'getPopulation'],
                'scopes' => [McpAddon::class, 'getScopes'],
            ],
        ],
    ],
    'blackcube/mcp' => [
        'endpoint' => '/mcp',
        'locale' => 'en',
        'maxTtlDays' => 180,
        'defaultTtlDays' => 90,
        'allowedHosts' => ['cms.example.com'],
    ],
];
```

`Psr\Clock\ClockInterface` is expected from the host application (`blackcube/oauth2` provides one); the package does not bind it.

`locale` (default `en`) is set on the translator for the endpoint only: every message sent back to the client, validation errors included, stays in that locale whatever the application locale.

`allowedHosts` (default `[]`) lists the host names the endpoint answers to; any other `Host` header gets a 403. Left empty, only the host of the current request is allowed. An application usually fills it from a comma-separated environment variable (`MCP_ALLOWED_HOSTS="cms.example.com, localhost"`, split on `/\s*,\s*/`).

Run the migrations, then create a key. Scopes are dboard permissions: `MCP:CONNECT` is mandatory, the others are the permissions the tools gate on (`McpAddon::getScopes()` lists them all). The token is printed once.

```bash
php yii.php migrate:up
php yii.php mcp:keyCreate --name="content agent" --scopes=MCP:CONNECT,CONTENT:VIEW
```

`mcp:keyList`, `mcp:keyRevoke` and `mcp:keyGc` complete the console side; the dboard "MCP keys" screen does the same, rotation included.

Point the MCP client at the endpoint (Streamable HTTP) with the token as a bearer:

```json
{
    "mcpServers": {
        "blackcube": {
            "type": "http",
            "url": "https://cms.example.com/mcp",
            "headers": {
                "Authorization": "Bearer <token>"
            }
        }
    }
}
```

An OAuth2-capable client needs no key: it discovers the authorization server from `/.well-known/oauth-protected-resource/mcp`, registers itself and asks an administrator for consent.

## Tests

```bash
vendor/bin/codecept run
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

## Author

Philippe Gaultier <philippe@blackcube.io>
