# mcp — Keys and addon

## MCP keys

`Models\ApiKey` (`src/Models/ApiKey.php:31`, table `mcpKeys`): `name`, `hash` (SHA-256 of the token, the token itself is never stored, `src/Helpers/ApiKeyToken.php:28-41`), `scopes` (dboard permission names), `dateExpire`, `revoked`, `dateLastUse`, `dateCreate`, `dateUpdate`.

`Services\ApiKeyService` (`src/Services/ApiKeyService.php`), wired with `maxTtlDays` and `defaultTtlDays` (`config/common/di.php:44-50`):

| Method | Effect |
|---|---|
| `create(name, scopes, ?ttlDays)` | checks the TTL cap and that every scope is published by `McpAddon::getScopes()`, generates the token, saves; returns `['token', 'id']`, the token shown once (`:40-60`) |
| `rotate(id, ?ttlDays)` | new token and hash, new expiration, `revoked` false (`:83-106`) |
| `update(id, name, scopes, ?dateExpire)` | metadata only, expiration under the cap (`:114-137`) |
| `revoke(id)` | `revoked` true (`:62-75`) |
| `getKey(id)`, `getKeys(includeExpired = false)`, `getExpiredIds(keys)` | reads (`:142-193`) |
| `gc()` | deletes revoked or expired rows (`:195-214`) |

Console (`config/console/params.php:17-24`): `mcp:keyCreate --name= --scopes=A,B [--ttl-days=]`, `mcp:keyList [--show-expired]`, `mcp:keyRevoke --id=`, `mcp:keyGc` (`src/Commands/*.php`).

## dboard addon (`src/Dboard/McpAddon.php`)

`McpAddon` implements the four dboard addon interfaces (`:42`):

- `getRbac()` → `McpRbac` (`MCP:CONNECT`, `MCP:CREATE`, `MCP:DELETE`, `MCP:UPDATE`, `MCP:VIEW`, role `MCP:MANAGER`, `src/Dboard/McpRbac.php:25-30`).
- `getPopulation()` → `mcp`; `getScopes()` → `MCP:CONNECT` plus the `PERMISSIONS` of every registered tool (`:62-71`): the scopes a client may be granted on the population and the scopes a key may carry.
- `getSidebar()` → an "MCP keys" entry for holders of `MCP:VIEW` (`:73-93`).
- `getRoutesGroup()` → `/mcp/keys` (`MCP:VIEW`), `/keys/create` (`MCP:CREATE`), `/keys/{id}/edit` (`MCP:UPDATE`), `/keys/{id}/delete` GET + DELETE (`MCP:DELETE`), `/keys/{id}/rotate` GET + POST (`MCP:UPDATE`), under the dboard prefix (`:95-146`).

Screens: `Dboard\Handlers\Keys\{Index,Create,Edit,DeleteInit,Delete,RotateInit,Rotate}` on `AbstractKeysHandler`, which injects `ApiKeyService`, renders from `@mcp/Dboard/Views` inside the dboard layout and groups the published scopes by role for the checkboxes (`src/Dboard/Handlers/AbstractKeysHandler.php:61-80`); `Dboard\Forms\KeyForm` (`name`, `ttlDays`, `dateExpire`, `scopes`, scenarios create / edit).

## OAuth2 clients

An LLM client that speaks OAuth2 needs no key: `/.well-known/oauth-protected-resource<endpoint>` names the dboard population server `/dboard/oauth/mcp`; the client registers (RFC 7591), the administrator consents to a subset of the published scopes, the token is bounded by that administrator's permissions on every request (`server.md`, `blackcube/dboard` `llm-doc/addons.md`).
