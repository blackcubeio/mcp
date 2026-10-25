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
 * schemaMapping.delete - Delete a schema mapping.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Delete
{
    public const NAME = 'schemaMapping.delete';
    public const PERMISSIONS = [
        Rbac::PERMISSION_XEO_DELETE,
    ];

    public function __construct(
        private readonly SchemaMappingService $schemaMappingService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Delete a SchemaSchema mapping, addressed by its schema pair. Mappings are cheap to rebuild (schemaMapping.create), hence the delete verb. Returns the deleted mapping.';
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
            ],
            'required' => [
                'regularElasticSchemaId',
                'xeoElasticSchemaId',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $regularElasticSchemaId, int $xeoElasticSchemaId): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->schemaMappingService->delete($regularElasticSchemaId, $xeoElasticSchemaId);
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
