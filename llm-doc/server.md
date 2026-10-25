# mcp — Server

## Request flow

1. `Middlewares\Locale` sets the translator locale to the `locale` param (`src/Middlewares/Locale.php:35-40`).
2. `Middlewares\Authentication` wraps the SDK `AuthorizationMiddleware` with `AuthorizationTokenValidator` and the resource metadata of the request; a refused request answers `401` / `403` with the `WWW-Authenticate` hint pointing at the metadata document (`src/Middlewares/Authentication.php:38-42`).
3. `Handlers\Mcp` reads the `AccessToken` request attribute for the granted scopes, builds the transport middleware stack (`CorsMiddleware`, `DnsRebindingProtectionMiddleware` on `allowedHosts`, the request host when the param is empty), creates the `StreamableHttpTransport` and runs the server built by `McpServerFactory` (`src/Handlers/Mcp.php:51-82`).

## Authentication (`src/Services/AuthorizationTokenValidator.php`)

| Credential | Detection | Check | Claims attached |
|---|---|---|---|
| dboard access token | two `.` in the Bearer (`:38`, `:47`) | `AccessTokenValidator::validate($token, 'mcp')` of `blackcube/dboard`: signature, `iss` = `aud` = `mcp`, not expired, active administrator; permissions = granted scopes ∩ permissions held now (`:56-68`) | `mcp.administratorId`, `mcp.administratorName` |
| MCP key | anything else | `ApiKey` by `ApiKeyToken::hash()`, not revoked, `dateExpire` in the future; `dateLastUse` refreshed (`:70-86`, `:109-120`) | `mcp.keyId`, `mcp.keyName` |

Both go through `allow()`: without `MCP:CONNECT` → `forbidden('insufficient_scope')`, else `AuthorizationResult::allow(new AccessToken($permissions, $claims))` (`:92-107`).

## Resource metadata (`src/Services/ResourceMetadataFactory.php`)

`fromRequest()` builds the RFC 9728 document from the request scheme and authority: `resource` = base URL + `endpoint`, `authorization_servers` = base URL + dboard `routePrefix` + `/oauth/mcp`, `scopes_supported` = `McpAddon::getScopes()` (`:40-51`). Served by `Handlers\ResourceMetadata` through the SDK `ProtectedResourceMetadataHandler` (`src/Handlers/ResourceMetadata.php:38-46`) at `/.well-known/oauth-protected-resource<endpoint>` (`METADATA_PATH`, `:31`). An OAuth2 client reads it, registers itself on the dboard population and sends the administrator to the consent screen (`blackcube/dboard` `llm-doc/addons.md`).

## Server factory (`src/Mcp/Server/McpServerFactory.php`)

`create(?CacheInterface $cache, array $scopes, ScopeGuard, ContainerInterface, ServerInfo): Server` (`:42-90`):

- `Server::builder()` with `ServerInfo` name and version (`config/common/di.php:51-57`: the `application.name` param or `Blackcube MCP`, version `1.0.0` unless `blackcube/mcp.version`), the Yii container for tool construction, the instructions text (`:122-130`, the five rules an agent reads at `initialize`).
- Session store: `CacheSessionStore` on the host cache, or a `FileCache` under `@runtime/mcp/cache` when the cache is missing or an `ArrayCache` (`:46-49`).
- Tools: for every class of `Registry::TOOLS`, `ScopeGuard::isGranted($scopes, $tool::PERMISSIONS)` → `addTool([$tool, 'handle'], NAME, null, description(), null, closedSchema(inputSchema()))` (`:51-72`). Tool instances come from the container, so a tool constructor takes services.
- `ToolListChangedNotifier(md5(json surface))` as event dispatcher; capabilities `tools` + `toolsListChanged` when at least one tool, `logging`, `completions`, no resources, no prompts (`:74-87`).

`CacheSessionStore` (`src/Mcp/Server/CacheSessionStore.php:27-61`): `exists` / `read` / `write` / `destroy` / `gc` on the PSR-16 cache, keys `mcp.session.<uuid>`, TTL 3600 s. `ToolListChangedNotifier` (`src/Mcp/Server/ToolListChangedNotifier.php:36-50`): on each `ResponseEvent`, compares the surface fingerprint with the one stored in the session and queues `notifications/tools/list_changed` on the session outgoing queue when it differs, so a key whose scopes changed sees its new tool list on its next request.
