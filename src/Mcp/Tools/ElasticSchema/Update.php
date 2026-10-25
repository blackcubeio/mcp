<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\ElasticSchema;

use Blackcube\Dcore\Enums\ElasticSchemaKind;
use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Models\Forms\ElasticSchemaForm;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * elasticSchema.update - Update an existing ElasticSchema (by id).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'elasticSchema.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_ELASTICSCHEMA_UPDATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update an existing ElasticSchema, identified by its id. Only the provided fields change. Fails if it does not exist. Returns the updated schema.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        $kindValues = [];
        foreach (ElasticSchemaKind::cases() as $schemaKind) {
            $kindValues[] = $schemaKind->value;
        }
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'ElasticSchema id to update',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New name',
                ],
                'kind' => [
                    'type' => 'string',
                    'enum' => $kindValues,
                    'description' => 'Where the schema applies',
                ],
                'schema' => [
                    'type' => 'object',
                    'description' => 'JSON-Schema object (type/properties/required)',
                ],
                'view' => [
                    'type' => 'string',
                    'description' => 'View path (optional)',
                ],
                'mdMapping' => [
                    'type' => 'string',
                    'description' => 'Markdown import/export mapping',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Active',
                ],
            ],
            'required' => [
                'id',
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $schema
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $id, ?string $name = null, ?string $kind = null, ?array $schema = null, ?string $view = null, ?string $mdMapping = null, ?bool $active = null): array|CallToolResult
    {
        $toolResult = null;
        $elasticSchema = ElasticSchema::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($elasticSchema === null) {
            $toolResult = CallToolResult::error([
                new TextContent('ElasticSchema not found: ' . $id),
            ]);
        } else {
            $provided = [];
            if ($name !== null) {
                $provided['name'] = $name;
            }
            if ($kind !== null) {
                $provided['kind'] = $kind;
            }
            if ($schema !== null) {
                $provided['schema'] = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            if ($view !== null) {
                $provided['view'] = $view;
            }
            if ($mdMapping !== null) {
                $provided['mdMapping'] = $mdMapping;
            }
            if ($active !== null) {
                $provided['active'] = $active;
            }
            $form = new ElasticSchemaForm();
            $form->setScenario('edit');
            $form->initFromModel($elasticSchema);
            $form->setId($id);
            $form->setPatchFields(array_keys($provided));
            $form->load($provided, '');
            if ($form->validate() === false) {
                $toolResult = CallToolResult::error([
                    new TextContent(implode('; ', $form->getValidationResult()->getErrorMessages())),
                ]);
            } else {
                $form->populateModel($elasticSchema);
                try {
                    $elasticSchema->save();
                    $toolResult = Index::item($elasticSchema);
                } catch (Throwable $e) {
                    $this->logger->error($e->getMessage(), [
                        'exception' => $e,
                    ]);
                    $toolResult = CallToolResult::error([
                        new TextContent($e->getMessage()),
                    ]);
                }
            }
        }

        return $toolResult;
    }
}
