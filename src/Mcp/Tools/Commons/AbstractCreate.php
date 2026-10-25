<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Commons;

use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Mcp\Services\ElementService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Creates a bare element node and places it in the tree, shared by content and
 * tag. A concrete tool sets NAME and TYPE, and supplies its extra node fields
 * (the only part that differs: content carries the publication window); the
 * depth limit comes from ElementService::MAX_LEVEL.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractCreate
{
    protected const TYPE = '';

    public function __construct(
        protected ElementService $elementService,
        protected LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        $fields = 'name, type, language, elasticSchema, active';
        $extraFields = static::extraFieldsSchema();
        if (empty($extraFields) === false) {
            $fields .= ', ' . implode(', ', array_keys($extraFields));
        }

        return 'Create a bare ' . static::TYPE . ' node (' . $fields . '), placed in the tree (without '
            . 'targetRoute it becomes a root; with targetRoute + mode it is positioned, max ' . ElementService::MAX_LEVEL[static::TYPE]
            . ' levels). The body is filled afterwards via ' . static::TYPE . '.update. Returns the created node.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        $properties = array_merge(
            [
                'name' => [
                    'type' => 'string',
                    'description' => ucfirst(static::TYPE) . ' name',
                ],
                'typeId' => [
                    'type' => 'integer',
                    'description' => 'Type id (defines the allowed blocs); resolve via type.list',
                ],
                'languageId' => [
                    'type' => 'string',
                    'description' => 'Language code (e.g. fr); resolve via language.list',
                ],
                'elasticSchemaId' => [
                    'type' => 'integer',
                    'description' => 'Page ElasticSchema id for the node data; resolve via elasticSchema.list',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Active switch (default false)',
                ],
            ],
            static::extraFieldsSchema(),
            [
                'targetRoute' => [
                    'type' => 'string',
                    'description' => 'Reference ' . static::TYPE . ' route (dcore-XXX) for placement; omit for a root',
                ],
                'mode' => [
                    'type' => 'string',
                    'enum' => [
                        TreePosition::Before->value,
                        TreePosition::Into->value,
                        TreePosition::After->value,
                    ],
                    'description' => 'Placement relative to targetRoute (default into)',
                ],
            ],
        );

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => [
                'name',
                'languageId',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $name, ?int $typeId = null, ?string $languageId = null, ?int $elasticSchemaId = null, bool $active = false, ?string $dateStart = null, ?string $dateEnd = null, ?string $targetRoute = null, string $mode = TreePosition::Into->value): array|CallToolResult
    {
        $toolResult = null;
        $fields = [
            'name' => $name,
            'active' => $active,
        ];
        if ($typeId !== null) {
            $fields['typeId'] = $typeId;
        }
        if ($languageId !== null) {
            $fields['languageId'] = $languageId;
        }
        if ($elasticSchemaId !== null) {
            $fields['elasticSchemaId'] = $elasticSchemaId;
        }
        if ($dateStart !== null) {
            $fields['dateStart'] = $dateStart;
        }
        if ($dateEnd !== null) {
            $fields['dateEnd'] = $dateEnd;
        }
        try {
            $toolResult = $this->elementService->create(static::TYPE, $fields, $targetRoute, $mode);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
            ]);
            $toolResult = CallToolResult::error([
                new TextContent($e->getMessage()),
            ]);
        }

        return $toolResult;
    }

    /**
     * Extra node-field properties, the only schema part that differs between
     * content and tag.
     *
     * @return array<string, mixed>
     */
    protected static function extraFieldsSchema(): array
    {
        return [];
    }
}
