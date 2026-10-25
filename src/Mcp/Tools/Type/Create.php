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
 * type.create - Create a Type (page type of contents/tags).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'type.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_TYPE_CREATE,
    ];

    public function __construct(
        private readonly TypeService $typeService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create a Type: the page type of contents/tags, mapping to a render handler and declaring which ElasticSchemas its blocs may use. Returns the created type.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Type name',
                ],
                'handler' => [
                    'type' => 'string',
                    'description' => 'Render handler/route this type maps to',
                ],
                'contentAllowed' => [
                    'type' => 'boolean',
                    'description' => 'Whether Contents can use this type (default true)',
                ],
                'tagAllowed' => [
                    'type' => 'boolean',
                    'description' => 'Whether Tags can use this type (default true)',
                ],
                'elasticSchemas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                    ],
                    'description' => 'Allowed bloc schema ids (resolve via elasticSchema.list)',
                ],
            ],
            'required' => [
                'name',
            ],
        ];
    }

    /**
     * @param array<int, int>|null $elasticSchemas
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $name, ?string $handler = null, bool $contentAllowed = true, bool $tagAllowed = true, ?array $elasticSchemas = null): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->typeService->create($name, $handler, $contentAllowed, $tagAllowed, $elasticSchemas);
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
