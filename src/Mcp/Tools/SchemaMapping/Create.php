<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\SchemaMapping;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\SchemaMappingService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * schemaMapping.create - Create a schema mapping.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'schemaMapping.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_XEO_CREATE,
    ];

    public function __construct(
        private readonly SchemaMappingService $schemaMappingService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create a SchemaSchema mapping: the xeo schema (xeoElasticSchemaId) fed by a regular schema (regularElasticSchemaId), with the field map as JSON ({"mapping": {sourceField: targetField}}). The pair must not exist yet. Returns the created mapping.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'regularElasticSchemaId' => [
                    'type' => 'integer',
                    'description' => 'Source schema id (kind page or bloc; resolve via elasticSchema.list)',
                ],
                'xeoElasticSchemaId' => [
                    'type' => 'integer',
                    'description' => 'Target xeo schema id (kind xeo)',
                ],
                'mapping' => [
                    'type' => 'string',
                    'description' => 'JSON field map, e.g. {"mapping": {"title": "name"}}',
                ],
            ],
            'required' => [
                'regularElasticSchemaId',
                'xeoElasticSchemaId',
                'mapping',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $regularElasticSchemaId, int $xeoElasticSchemaId, string $mapping): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->schemaMappingService->create($regularElasticSchemaId, $xeoElasticSchemaId, $mapping);
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
}
