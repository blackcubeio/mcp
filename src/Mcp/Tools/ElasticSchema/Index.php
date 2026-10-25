<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\ElasticSchema;

use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dboard\Components\Rbac;

/**
 * elasticSchema.list - List the ElasticSchemas (JSON-Schemas of the dynamic fields).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'elasticSchema.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_ELASTICSCHEMA_VIEW,
    ];

    public static function description(): string
    {
        return 'List the ElasticSchemas (JSON-Schemas defining the dynamic fields of contents, tags, blocs and xeo). kind = common|page|bloc|xeo; mdMapping drives markdown import/export. Resolves the elasticSchemaId carried by content nodes and blocs. Prefer filtering (kind, or a single id) over dumping the full list: kind=bloc gives the schemas usable in blocs.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kind' => [
                    'type' => 'string',
                    'enum' => [
                        'common',
                        'page',
                        'bloc',
                        'xeo',
                    ],
                    'description' => 'Filter by schema kind',
                ],
                'id' => [
                    'type' => 'integer',
                    'description' => 'Fetch a single schema by its id',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(?string $kind = null, ?int $id = null): array
    {
        $items = [];
        $elasticSchemasQuery = ElasticSchema::query()->orderBy([
            'name' => SORT_ASC,
            'id' => SORT_ASC,
        ]);
        if ($kind !== null) {
            $elasticSchemasQuery->andWhere([
                'kind' => $kind,
            ]);
        }
        if ($id !== null) {
            $elasticSchemasQuery->andWhere([
                'id' => $id,
            ]);
        }
        /** @var ElasticSchema $elasticSchema */
        foreach ($elasticSchemasQuery->each() as $elasticSchema) {
            $items[] = self::item($elasticSchema);
        }

        return [
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function item(ElasticSchema $elasticSchema): array
    {
        $schema = $elasticSchema->getSchema();
        return [
            'id' => $elasticSchema->getId(),
            'name' => $elasticSchema->getName(),
            'kind' => $elasticSchema->getKind()->value,
            'schema' => $schema !== null ? json_decode($schema, true) : null,
            'mdMapping' => $elasticSchema->getMdMapping(),
            'builtin' => $elasticSchema->isBuiltin(),
            'hidden' => $elasticSchema->isHidden(),
            'active' => $elasticSchema->isActive(),
        ];
    }
}
