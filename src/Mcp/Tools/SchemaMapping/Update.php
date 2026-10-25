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
 * schemaMapping.update - Update the field map of a schema mapping.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'schemaMapping.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_XEO_UPDATE,
    ];

    public function __construct(
        private readonly SchemaMappingService $schemaMappingService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update the JSON field map of a SchemaSchema mapping, addressed by its schema pair (the pair is the identity: to change it, delete and recreate). Returns the updated mapping.';
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
                    'description' => 'Source schema id of the pair',
                ],
                'xeoElasticSchemaId' => [
                    'type' => 'integer',
                    'description' => 'Target xeo schema id of the pair',
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
            $toolResult = $this->schemaMappingService->update($regularElasticSchemaId, $xeoElasticSchemaId, $mapping);
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
