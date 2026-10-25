<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dcore\Models\Author;
use Blackcube\Dcore\Models\Bloc;
use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dcore\Models\Language;
use Blackcube\Dcore\Models\LlmMenu;
use Blackcube\Dcore\Models\Menu;
use Blackcube\Dcore\Models\Parameter;
use Blackcube\Dcore\Models\Sitemap;
use Blackcube\Dcore\Models\Slug;
use Blackcube\Dcore\Models\Tag;
use Blackcube\Dcore\Models\Type;
use Blackcube\Dcore\Models\Xeo;
use Blackcube\Dcore\Services\MapperService;
use Blackcube\Mcp\Mcp\Tools\Registry;

/**
 * Describes the dcore content model so an agent (or a human) knows what each
 * entity is for and how they relate, without rediscovering the domain.
 *
 * Everything shown for a model is derived from the code through
 * MapperService::describe() — field/relation lists, types (signatures) and
 * descriptions (#[Exportable]/#[Importable] attributes on the models) — and the
 * tool surface comes from Tools\Registry, so none of it can drift.
 * documentation() only carries the ecosystem prose (roles, rules) per entity.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class InfoService
{
    public function __construct(
        private readonly MapperService $mapper,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getModel(): array
    {
        $modelDescription = [];
        foreach ($this->documentation() as $name => $doc) {
            $entry = [
                'role' => $doc['role'],
            ];
            if (isset($doc['model']) === true) {
                $shape = $this->mapper->describe($doc['model']);
                $entry['fields'] = $this->fieldSummaries($shape['fields']);
                $entry['relations'] = $this->relationSummaries($shape['relations']);
            }
            if (isset($doc['rules']) === true) {
                $entry['rules'] = $doc['rules'];
            }
            $modelDescription[$name] = $entry;
        }
        $modelDescription['tools'] = $this->toolSummaries();
        return $modelDescription;
    }

    /**
     * Field summary for the agent, fully derived from the model code through
     * MapperService::describe(): type from the signature, description from the
     * attribute (an undocumented field surfaces with an empty string, visible
     * instead of hidden), write flags the fields accepted on import.
     *
     * @param array<string, array{read: bool, write: bool, type: string|null, format: string|null, description: string|null}> $fields
     * @return array<string, array<string, mixed>>
     */
    private function fieldSummaries(array $fields): array
    {
        $fieldSummaries = [];
        foreach ($fields as $name => $field) {
            $summary = [
                'type' => $field['type'],
                'description' => $field['description'] ?? '',
            ];
            if ($field['write'] === true) {
                $summary['write'] = true;
            }
            $fieldSummaries[$name] = $summary;
        }
        return $fieldSummaries;
    }

    /**
     * Relation summary: description from the attribute plus the whitelist of
     * exported fields when the relation is narrowed.
     *
     * @param array<string, array{read: bool, fields: array<string>|null, description: string|null}> $relations
     * @return array<string, array<string, mixed>>
     */
    private function relationSummaries(array $relations): array
    {
        $relationSummaries = [];
        foreach ($relations as $name => $relation) {
            $summary = [
                'description' => $relation['description'] ?? '',
            ];
            if ($relation['fields'] !== null) {
                $summary['fields'] = $relation['fields'];
            }
            $relationSummaries[$name] = $summary;
        }
        return $relationSummaries;
    }

    /**
     * Tool surface derived from the real registry (Tools\Registry), so the
     * documented surface can never drift from the shipped one. The key scopes
     * filter tools/list; this section shows the full product surface.
     *
     * @return array<string, mixed>
     */
    private function toolSummaries(): array
    {
        $domainVerbs = [];
        foreach (Registry::TOOLS as $toolClass) {
            [$domain, $verb] = explode('.', $toolClass::NAME, 2);
            $domainVerbs[$domain][] = $verb;
        }
        ksort($domainVerbs);
        $toolSummaries = [
            'role' => 'Verbs available per domain. The key scopes filter tools/list: this is the full product surface.',
        ];
        foreach ($domainVerbs as $domain => $verbs) {
            sort($verbs);
            $toolSummaries[$domain] = implode(', ', $verbs);
        }
        $toolSummaries['rules'] = [
            'A domain without a delete verb is never deleted: disable the element with update {active: false} instead.',
        ];
        return $toolSummaries;
    }

    /**
     * Hand-maintained ecosystem knowledge: the role and rules of each entity
     * plus the transverse concepts (Hazeltree, Elastic, FileReference, route).
     * Fields, relations, types, descriptions and the tool surface are derived.
     *
     * @return array<string, array<string, mixed>>
     */
    private function documentation(): array
    {
        return [
            'Hazeltree' => [
                'role' => 'Tree storage model: a nested set whose left/right bounds are rational fractions. Any read is a single range query, whatever the lookup (O(1) query). Inserting or moving a node updates only that node and its descendants (O(k), k = subtree size), never the other nodes — no global renumbering (a classic integer nested set would be O(n)).',
            ],
            'Elastic' => [
                'role' => 'Clean EAV: an entity holds dynamic fields whose names, types and validation come from an ElasticSchema (a JSON Schema), instead of fixed table columns.',
            ],
            'FileReference' => [
                'role' => 'Files (images, documents) are never inlined. A field value shaped like "@store/path/file.ext" — or several, comma-separated — is a file reference, NOT a URL: fetch the binary with the file.fetch tool.',
            ],
            'route' => [
                'role' => 'A route is the opaque internal id of a routable element, shaped dcore-XXX — an opaque key, never parse it. Contents and tags are routable today, but the model is open: other element types, including ones external to the CMS, can or will be routable. Routable does not mean reachable: an element is served only when it has a slug (its URL); without one it still exists and keeps its route, but has no URL yet. A route addresses an element in content.get / tag.get / xeo.get, links translations, and is what a menu or llmMenu entry targets.',
            ],
            'Content' => [
                'role' => 'Editorial node of the content tree (stored as a Hazeltree). Taggable, unlimited tree depth.',
                'model' => Content::class,
                'rules' => [
                    'Publicly visible only when active=true AND now is within [dateStart, dateEnd].',
                    'Admin tools expose the raw view: inactive and out-of-window nodes are returned too.',
                ],
            ],
            'Tag' => [
                'role' => 'Taxonomy node, same shape as Content (tree + Elastic blocs) but NOT taggable and the tree is capped at 2 levels.',
                'model' => Tag::class,
            ],
            'Slug' => [
                'role' => 'The URL of a Content or a Tag, scoped to a Host.',
                'model' => Slug::class,
                'rules' => [
                    'Carries the xeo (SEO/GEO) and the sitemap entry.',
                    'An element with no slug is non-routable (exists but has no URL).',
                ],
            ],
            'Xeo' => [
                'role' => 'SEO/GEO metadata of a slug. Reached through the slug, never directly from the node.',
                'model' => Xeo::class,
            ],
            'Sitemap' => [
                'role' => 'Sitemap entry of a slug. Reached through the slug.',
                'model' => Sitemap::class,
            ],
            'Bloc' => [
                'role' => 'A unit of content with Elastic (EAV) fields defined and validated by its ElasticSchema; attached to a Content or a Tag in an explicit order.',
                'model' => Bloc::class,
            ],
            'ElasticSchema' => [
                'role' => 'The JSON Schema (Elastic) defining the fields of a bloc/page/xeo.',
                'model' => ElasticSchema::class,
            ],
            'Type' => [
                'role' => 'The type of a Content/Tag: it lists the allowed ElasticSchemas and maps to a render handler/route.',
                'model' => Type::class,
            ],
            'Menu' => [
                'role' => 'Navigation tree (Hazeltree, max 4 levels). Points to a route + queryString, scoped by host and language. Not tied to a Content/Tag id.',
                'model' => Menu::class,
            ],
            'LlmMenu' => [
                'role' => 'llms.txt navigation tree. Each entry references either a Content (contentId) or a Tag (tagId).',
                'model' => LlmMenu::class,
            ],
            'Language' => [
                'role' => 'A site language (id e.g. "fr"). main flags the default language; active toggles availability.',
                'model' => Language::class,
            ],
            'Parameter' => [
                'role' => 'Global key/value setting addressed by domain + name (e.g. PROJECT.NAME).',
                'model' => Parameter::class,
            ],
            'Author' => [
                'role' => 'A content author (schema.org Person), attachable to Contents and Tags.',
                'model' => Author::class,
            ],
        ];
    }
}
