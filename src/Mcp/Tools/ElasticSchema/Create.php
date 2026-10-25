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
 * elasticSchema.create - Create an ElasticSchema (JSON-Schema of dynamic fields).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'elasticSchema.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_ELASTICSCHEMA_CREATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create an ElasticSchema (JSON-Schema defining dynamic fields). kind = common|page|bloc|xeo. Returns the created schema, including its generated id.';
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
                'name' => [
                    'type' => 'string',
                    'description' => 'Schema name',
                ],
                'kind' => [
                    'type' => 'string',
                    'enum' => $kindValues,
                    'description' => 'Where the schema applies (default common)',
                ],
                'schema' => [
                    'type' => 'object',
                    'description' => 'JSON-Schema object (type/properties/required); defaults to an empty object schema',
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
                    'description' => 'Active (default true)',
                ],
            ],
            'required' => [
                'name',
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $schema
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $name, string $kind = 'common', ?array $schema = null, ?string $view = null, ?string $mdMapping = null, bool $active = true): array|CallToolResult
    {
        $toolResult = null;
        $provided = [
            'name' => $name,
            'kind' => $kind,
            'active' => $active,
        ];
        if ($schema !== null) {
            $provided['schema'] = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if ($view !== null) {
            $provided['view'] = $view;
        }
        if ($mdMapping !== null) {
            $provided['mdMapping'] = $mdMapping;
        }
        $form = new ElasticSchemaForm();
        $form->setScenario('create');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            $toolResult = CallToolResult::error([
                new TextContent(implode('; ', $form->getValidationResult()->getErrorMessages())),
            ]);
        } else {
            $elasticSchema = new ElasticSchema();
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

        return $toolResult;
    }
}
