<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Type;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\TypeService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * type.update - Update a Type's name and/or its allowed ElasticSchemas.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'type.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_TYPE_UPDATE,
    ];

    public function __construct(
        private readonly TypeService $typeService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update a Type: its name, its contentAllowed/tagAllowed flags, and/or the list of ElasticSchemas its blocs may use (elasticSchemas replaces the whole list, [] clears it). Only the provided fields change. The handler and Type creation are managed in the admin. Returns the updated type.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Type id to update',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New type name',
                ],
                'contentAllowed' => [
                    'type' => 'boolean',
                    'description' => 'Whether contents may use this type',
                ],
                'tagAllowed' => [
                    'type' => 'boolean',
                    'description' => 'Whether tags may use this type',
                ],
                'elasticSchemas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                    ],
                    'description' => 'Allowed ElasticSchema ids (replaces the whole list)',
                ],
            ],
            'required' => [
                'id',
            ],
        ];
    }

    /**
     * @param array<int, int>|null $elasticSchemas
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $id, ?string $name = null, ?bool $contentAllowed = null, ?bool $tagAllowed = null, ?array $elasticSchemas = null): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->typeService->update($id, $name, $contentAllowed, $tagAllowed, $elasticSchemas);
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
