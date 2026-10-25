# mcp — Tools

## Registry and abstracts

`Mcp\Tools\Registry::TOOLS` lists the 67 tool classes (`src/Mcp/Tools/Registry.php:28-97`); `McpServerFactory` registers the granted ones and `InfoService` derives the `tools` section of `info.model` from the same list (`src/Services/InfoService.php:127-137`). A tool is a class with `NAME`, `PERMISSIONS`, `TYPE` (content / tag) for the shared ones, `description()`, `inputSchema()` and `handle(...)` whose parameters mirror the schema; it returns an array or a `CallToolResult::error([...])` (`src/Mcp/Tools/Commons/AbstractGet.php:63-83`).

| Abstract (`src/Mcp/Tools/Commons/`) | `handle()` | Service |
|---|---|---|
| `AbstractIndex` | `(?route, ?mode, page = 1, pageSize = 20)`: `ContentTreeMode` `roots`, `children`, `descendants`, `parent`, `ancestors`, `siblings`; relative modes need a route (`AbstractIndex.php:74-96`) | `ElementService::list()` |
| `AbstractSearch` | `(query, page, pageSize)` | `ElementService::search()` |
| `AbstractGet` | `(route)` → the node with `serializedRelations()` (content: slug, blocs, tags, authors) | `ElementService::get()` |
| `AbstractCreate` | `(name, ?typeId, ?languageId, ?elasticSchemaId, active = false, ?dateStart, ?dateEnd, ?targetRoute, mode = into)` + `extraFieldsSchema()` (`AbstractCreate.php:111`) | `ElementService::create()` |
| `AbstractUpdate` | `(route, ?content, ?data, ?slug, ?sitemap, ?xeo, ?blocs)`: each section optional, applied in one transaction; `blocs` is the complete list (`AbstractUpdate.php:37-50`, `:224-258`) | `ElementService::update()` |
| `AbstractMove` | `(route, targetRoute, mode)` | `ElementService::move()` |
| `AbstractTreeGet` / `TreeMove` / `TreeDelete` | `(id)`, `(id, targetId, mode)`, `(id)` for the id-based trees | `TreeServiceInterface::get()` / `move()` / `delete()` (`src/Interfaces/TreeServiceInterface.php:26-36`): `MenuService`, `LlmMenuService` |

## Tools by domain (name → permission)

| Domain | Tools |
|---|---|
| `info` | `info.model` (the dcore model: role, fields, relations, rules per entity, plus the tool surface), `info.stats` (dashboard counts) → `MCP:CONNECT` |
| `content` | `content.list`, `content.search`, `content.get` → `CONTENT:VIEW`; `content.create` → `CONTENT:CREATE`; `content.update`, `content.move`, `content.tag` → `CONTENT:UPDATE` |
| `tag` | same verbs on `TAG:*`; `tag.content` sets the contents of a tag → `TAG:UPDATE` |
| `menu`, `llmMenu` | `list`, `get` → `VIEW`; `create` → `CREATE`; `update`, `move` → `UPDATE`; `delete` → `DELETE` (`MENU:*`, `LLMMENU:*`); trees capped at 4 and 3 levels (`src/Services/MenuService.php:33`, `LlmMenuService.php:35`) |
| `md` | `md.export` (authorable markdown with the bloc dictionaries) → `IA:EXPORT`; `md.import` (replaces page data and blocs from that format) → `IA:IMPORT` |
| `translation` | `translation.link`, `translation.unlink` → `CONTENT:UPDATE` or `TAG:UPDATE` |
| `slug` | `slug.list`, `slug.get` → `SLUG:VIEW`; `slug.create`, `slug.update`, `slug.delete` → `SLUG:*` (redirect slugs: path, targetUrl, httpCode, host) |
| `xeo` | `xeo.get` → `CONTENT:VIEW` or `TAG:VIEW`; `xeo.refresh` (rebuilds the xeo blocs from the schema mappings) → `CONTENT:UPDATE` or `TAG:UPDATE` |
| `globalXeo` | `globalXeo.list`, `globalXeo.get` → `XEO:VIEW`; `globalXeo.set` → `XEO:UPDATE`; `globalXeo.delete` → `XEO:DELETE` (per host and kind) |
| `schemaMapping` | `list` → `XEO:VIEW`; `create` → `XEO:CREATE`; `update` → `XEO:UPDATE`; `delete` → `XEO:DELETE` |
| `file` | `file.fetch` (bytes behind a `@blfs/` reference) → `CONTENT:VIEW` or `TAG:VIEW`; `file.upload` (base64 or url → `@bltmp/` reference) → `CONTENT:UPDATE` or `TAG:UPDATE` |
| `type`, `elasticSchema`, `parameter`, `host`, `language`, `author` | `list` → `VIEW`; `create` → `CREATE`; `update` → `UPDATE` on their prefix; `parameter.delete` → `PARAMETER:DELETE` |

## Services behind the tools

- `ElementService` (`src/Services/ElementService.php:55`): the shared read / write logic of content and tag (`MODEL_CLASS`, `MAX_LEVEL` 99 / 2, `FORM_CLASS`, `:60-79`); `get()` loads with `slug` and `blocs`, `getXeo()`, `list()`, `search()`, `create()`, `update()`, `refreshXeo()`, `move()`, `setContentTags()`, `setTagContents()`. Returns plain arrays or `null`; the tools turn `null` into an error.
- `AbstractHazeltree::reorder()` / `hazeltree()` for the tree moves; `MenuService`, `LlmMenuService`, `SlugService`, `GlobalXeoService` (kinds and default names), `SchemaMappingService`, `TypeService`, `TranslationService`: one method per verb, arrays in and out.
- `ExportService` extends dcore `MapperService` with `FileReferenceFormatter` so file fields come back as `@blfs/` paths, never base64 (`src/Services/ExportService.php:31-53`); `FileUploadService::upload($filename, ?$content, ?$url)` stores in the temporary area and returns `['ref', 'filename', 'size']` (`src/Services/FileUploadService.php:39`).
- `InfoService::getModel()`: `documentation()` prose per entity + `MapperService::describe()` for fields and relations + the registry for tools (`src/Services/InfoService.php:52-71`).

## Forms

The writes go through `blackcube/bridge-form-model` forms with `#[Bridge]` setters (`src/Models/Forms/*`): `AbstractElementForm` carries the node fields and tree placement shared by `ContentForm` and `TagForm`, which add their own through `extraFields()` / `extraRules()` (`src/Models/Forms/AbstractElementForm.php:24-27`). `PatchScenarioTrait::setPatchFields()` restricts validation and population to the fields present in the input, within the current scenario (`src/Traits/PatchScenarioTrait.php:14-16`, `:27-46`): an update never blanks an omitted field. `ElasticFormHelper::apply($formClass, $model, $elasticData)` validates the `data` section against the node schema.
