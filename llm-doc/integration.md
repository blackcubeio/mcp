# mcp — Integration

## Split

- Package: the endpoint, authentication, tools, services, forms, key management (console and dboard screens), the addon, migrations, translations.
- Application: a Yii3 application with `blackcube/dboard` configured, `Psr\Clock\ClockInterface` bound (`blackcube/oauth2` provides one; the package does not bind it, `config/common/di.php:32-34`), a shared PSR-16 cache for sessions, the `addons` declaration and the migration namespace.

## config-plugin (`config/configuration.php:12-20`)

| Group | File | Content |
|---|---|---|
| `params` | `config/common/params.php` | `blackcube/mcp`: `endpoint` `/mcp`, `locale` `en`, `maxTtlDays` 180, `defaultTtlDays` 90, `allowedHosts` `[]` (`:12-18`); optional `name` and `version` for `ServerInfo` |
| `di` | `config/common/di.php` | `ClientInterface` → Guzzle `Client`, `AuthorizationTokenValidatorInterface` → `AuthorizationTokenValidator`, `ResourceMetadataFactory` (endpoint), `ApiKeyService` (TTLs), `ServerInfo`, `Mcp` (allowed hosts), `Locale` (locale), `translation.mcp` category source on the dboard `MessageSource` (`:35-80`) |
| `bootstrap` | `config/common/bootstrap.php` | `@mcp` → the package `src/` |
| `routes` | `config/routes.php` | the endpoint route and the metadata route |
| `params-console` | `config/console/params.php` | the four `mcp:key*` commands |

## Host parameters

```php
'yiisoft/db-migration' => ['sourceNamespaces' => ['Blackcube\Dboard\Migrations', 'Blackcube\Dcore\Migrations', 'Blackcube\Mcp\Migrations']],
'blackcube/dboard' => ['addons' => ['mcp' => [
    'rbac' => [McpAddon::class, 'getRbac'],
    'sidebar' => [McpAddon::class, 'getSidebar'],
    'routes' => [McpAddon::class, 'getRoutesGroup'],
    'population' => [McpAddon::class, 'getPopulation'],
    'scopes' => [McpAddon::class, 'getScopes'],
]]],
'blackcube/mcp' => ['allowedHosts' => ['cms.example.com']],
```

`allowedHosts` empty means the host of the current request only; any other `Host` header is refused by `DnsRebindingProtectionMiddleware` (`src/Handlers/Mcp.php:59-71`). Migrations: `mcpKeys` (`M000000010000CreateMcpKeys.php:31`) and the `mcp` translations seeded into the dboard `yii_source_message` / `yii_message` tables (`M000000010001SeedMcpTranslations.php:33-76`), so the dboard migration must run first.

## Client

```json
{"mcpServers": {"blackcube": {"type": "http", "url": "https://cms.example.com/mcp", "headers": {"Authorization": "Bearer <token>"}}}}
```

The token is an MCP key (`mcp:keyCreate` or the dboard screen) or a dboard access token obtained through the OAuth2 flow discovered from `/.well-known/oauth-protected-resource/mcp` (`README.md:74-90`).

## Tests

One Codeception suite, `Integration` (`tests/Integration.suite.yml`): one round-trip Cest per domain (`ContentRoundTripCest`, `TagRoundTripCest`, `MenuRoundTripCest`, `LlmMenuRoundTripCest`, `SlugRoundTripCest`, `XeoRefreshRoundTripCest`, `GlobalXeoRoundTripCest`, `SchemaMappingRoundTripCest`, `TypeRoundTripCest`, `ElasticSchemaRoundTripCest`, `ParameterRoundTripCest`, `HostRoundTripCest`, `LanguageRoundTripCest`, `AuthorRoundTripCest`, `FileRoundTripCest`, `MdRoundTripCest`, `TranslationRoundTripCest`), plus `ApiKeyServiceCest`, `KeyFormCest`, `InfoModelCest`, on a real MySQL database (`tests/Support/DatabaseCestTrait.php`, `ServicesCestTrait.php`); `vendor/bin/codecept run`.
