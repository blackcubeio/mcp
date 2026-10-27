# blackcube/mcp — LLM index

**Role:** the MCP server of a Blackcube site: one Streamable HTTP endpoint, mounted as a Yii3 route, through which an agent administers the `blackcube/dcore` data (content and tag trees, blocs, menus, slugs, SEO, referentials, files, markdown round trips) with the same forms and services as the dboard screens. A credential is either an opaque MCP key or an OAuth2 access token of the dboard `mcp` population; both carry dboard permissions, and a tool is exposed only when the credential holds one of the permissions it declares. The package is also a dboard addon: RBAC items, sidebar entry, key-management screens, OAuth2 population.

## Package card

| | |
|---|---|
| Namespace | `Blackcube\Mcp` |
| Endpoint | `Handlers\Mcp` (the transport), `Handlers\ResourceMetadata` (RFC 9728 document), `Middlewares\Authentication`, `Middlewares\Locale`, `Mcp\Server\McpServerFactory`, `CacheSessionStore`, `ToolListChangedNotifier`, `ServerInfo` |
| Tools | `Mcp\Tools\Registry::TOOLS` (67 classes), `Mcp\Tools\Commons\Abstract{Index,Search,Get,Create,Update,Move,TreeGet,TreeMove,TreeDelete}`, one directory per domain (`Info`, `Content`, `Tag`, `Menu`, `LlmMenu`, `Md`, `Translation`, `Slug`, `Xeo`, `GlobalXeo`, `File`, `Type`, `ElasticSchema`, `SchemaMapping`, `Parameter`, `Host`, `Language`, `Author`) |
| Services | `ElementService`, `MenuService`, `LlmMenuService` (on `AbstractHazeltree`), `SlugService`, `GlobalXeoService`, `SchemaMappingService`, `TypeService`, `TranslationService`, `FileUploadService`, `ExportService`, `InfoService`, `ScopeGuard`, `ApiKeyService`, `AuthorizationTokenValidator`, `ResourceMetadataFactory` |
| Keys | `Models\ApiKey`, `Helpers\ApiKeyToken`, `Commands\Key{Create,List,Revoke,Gc}Command`, `Dboard\McpAddon`, `Dboard\McpRbac`, `Dboard\Handlers\Keys\*` on `Dboard\Handlers\AbstractKeysHandler`, `Dboard\Forms\KeyForm`, views `src/Dboard/Views/Keys/*` |
| Forms | `Models\Forms\AbstractElementForm` (+ `ContentForm`, `TagForm`), `BlocForm`, `XeoForm`, `SlugForm`, `SitemapForm`, `MenuForm`, `LlmMenuForm`, `HostForm`, `LanguageForm`, `TypeForm`, `ElasticSchemaForm`, `SchemaSchemaForm`, `GlobalXeoForm`, `ParameterForm`, `AuthorForm`, `HazeltreeReorderForm`; `Traits\PatchScenarioTrait`; `Models\Formatters\FileReferenceFormatter`; `Helpers\ElasticFormHelper` |
| Requires | PHP ^8.2, `mcp/sdk` ^0.9, `blackcube/dcore`, `dboard`, `handler`, `active-record`, `injector`, `bridge-form-model` ^1.0, `guzzlehttp/guzzle` ^8.3, PSR http-message / server-handler / server-middleware, `yiisoft/active-record`, `aliases`, `cache` + `cache-file`, `csrf`, `db` + `db-migration`, `form-model`, `hydrator`, `injector`, `log`, `yii-console` (`composer.json:30-55`) |
| Yii config | config-plugin (`config/configuration.php:12-20`): `config/common/params.php` (`blackcube/mcp`: `endpoint`, `locale`, `maxTtlDays`, `defaultTtlDays`, `allowedHosts`), `config/common/di.php`, `config/common/bootstrap.php` (`@mcp` alias), `config/routes.php`, `config/console/params.php` (`mcp:keyCreate`, `keyList`, `keyRevoke`, `keyGc`) |
| Migrations | `M000000010000CreateMcpKeys` (`mcpKeys`), `M000000010001SeedMcpTranslations` (category `mcp` into the dboard i18n tables from `src/i18n/<locale>/mcp.php`) |
| License | BSD-3-Clause |

## Rules

- One route, `GET|POST|DELETE|OPTIONS <endpoint>`, CSRF disabled, `Locale` then `Authentication` then `Handlers\Mcp` (`config/routes.php:24-34`); the metadata document under `/.well-known/oauth-protected-resource<endpoint>` (`:35-37`).
- Two credentials, one rule: a Bearer with two dots is a dboard access token checked by `AccessTokenValidator` for the `mcp` population, anything else is an MCP key looked up by SHA-256 hash; both need `MCP:CONNECT`, both end as an SDK `AccessToken` whose scopes are dboard permissions (`src/Services/AuthorizationTokenValidator.php:45-107`).
- A tool declares `NAME` and `PERMISSIONS`; it is registered for the session only when one of its permissions is granted (`ScopeGuard::isGranted()`, `src/Services/ScopeGuard.php:28-37`, `McpServerFactory.php:52-72`). `tools/list` is therefore the authorized surface; `info.model` lists the full one.
- Input schemas are closed: `additionalProperties: false` is forced on every object so a misspelled argument is refused instead of silently dropped (`McpServerFactory.php:105-120`).
- Elements are addressed by their dcore route (`dcore-c-N` / `dcore-t-N`), never by id: `Element::createFromRoute()` in `AbstractGet::handle()` (`src/Mcp/Tools/Commons/AbstractGet.php:66`); menus, llm menus, slugs and referentials use their integer id.
- Nothing is deleted where no `delete` verb exists: disable with `update {active: false}`; `content.update` / `tag.update` take the COMPLETE `blocs` list, a bloc left out is deleted with its files (`AbstractUpdate.php:41-49`).
- Files travel as references: reads return `@blfs/...` paths (`ExportService`, `src/Services/ExportService.php:19-25`), `file.fetch` returns the bytes, `file.upload` returns a `@bltmp/...` reference the next save moves into place (`FileUploadService.php:19-23`).
- Sessions live in the host PSR-16 cache under `mcp.session.<uuid>`, TTL 3600 s; an `ArrayCache` or no cache is replaced by a `FileCache` under `@runtime/mcp/cache` (`McpServerFactory.php:46-49`, `CacheSessionStore.php:27-34`). A changed tool surface is signalled with `tools/list_changed` on the next response (`ToolListChangedNotifier.php:20-30`).
- Every message sent to the agent is in the `locale` param (default `en`), whatever the application locale (`src/Middlewares/Locale.php:35-40`).

## Parts

| File | Content | Read when |
|---|---|---|
| `server.md` | The request flow, authentication, the server factory, sessions, notifications, instructions, resource metadata. | before mounting or debugging the endpoint |
| `tools.md` | The registry, the abstracts, every tool with its permissions, the services behind them, forms and patch semantics. | before adding or calling a tool |
| `keys.md` | MCP keys: model, service, console commands, dboard screens, the addon and its population. | before issuing a credential |
| `integration.md` | config-plugin, parameters, what the host provides, migrations, client configuration, tests. | before plugging the package in |
| `extension.md` | What extends and what does not. | before extending |
