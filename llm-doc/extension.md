# mcp — Extension

## Extension points

| Point | Where | Use |
|---|---|---|
| Endpoint path, locale, TTLs, allowed hosts, server name and version | `blackcube/mcp` params | no code |
| A new tool | a class with `NAME`, `PERMISSIONS`, `description()`, `inputSchema()`, `handle()`, listed in a `Registry::TOOLS` subclass constant and a `McpServerFactory` reading it; or a subclass of a `Commons\Abstract*` with its own `TYPE` / schema hooks | another entity, another verb |
| Tool permissions | the `PERMISSIONS` constant: any dboard or addon permission name; `McpAddon::getScopes()` publishes them automatically | finer gating |
| Element serialization | `AbstractGet::serializedRelations()`, `AbstractUpdate::nodeFieldsSchema()`, `AbstractCreate::extraFieldsSchema()` | other relations or fields |
| Forms | `AbstractElementForm::extraFields()` / `extraRules()`, `PatchScenarioTrait` on any bridge form | validation of new fields |
| Credential validation | bind `Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface` to another class in the host DI (`config/common/di.php:37`) | other token formats |
| Session storage | the host `CacheInterface`: Redis, file, … (`McpServerFactory.php:46-49`) | shared sessions across workers |
| HTTP client of `file.upload` | bind `GuzzleHttp\ClientInterface` otherwise (`config/common/di.php:36`) | proxies, timeouts |
| Key screens | extend `Dboard\Handlers\AbstractKeysHandler`, own views alias | other layouts |
| Server instructions | `McpServerFactory::instructions()` is `private static`: a subclass of the factory with its own text, called from a `Handlers\Mcp` subclass | other agent guidance |

## Not extensible

| Element | Reason |
|---|---|
| Registry and factory wiring | `Registry::TOOLS` is a constant read by `McpServerFactory::create()` (static) and `InfoService`; adding a tool means a new registry plus a factory that reads it |
| Route addressing | `Element::createFromRoute()` on `dcore-c-N` / `dcore-t-N`; ids are not accepted for content and tag |
| `MCP:CONNECT` requirement | hardcoded in `AuthorizationTokenValidator::allow()` (`:94`) |
| Key token format and hash | `bin2hex(random_bytes(32))`, SHA-256 (`src/Helpers/ApiKeyToken.php:28-41`) |
| Credential detection | two dots = JWT, else key (`AuthorizationTokenValidator.php:38`, `:47`) |
| Complete-list semantics of `blocs` | `ElementService::update()`: omitted blocs are deleted |
| Closed input schemas | `closedSchema()` forces `additionalProperties: false` on every tool (`McpServerFactory.php:105-120`) |
| Capabilities | no resources, no prompts (`McpServerFactory.php:77-87`) |
| Tree depth | `ElementService::MAX_LEVEL` (99 / 2), `MenuService` 4, `LlmMenuService` 3 |
