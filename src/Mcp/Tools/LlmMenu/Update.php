<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\LlmMenu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\LlmMenuService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * llmMenu.update - Update a llms.txt navigation node.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'llmMenu.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_LLMMENU_UPDATE,
    ];

    public function __construct(
        private readonly LlmMenuService $llmMenuService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update a llms.txt navigation node: name, description and/or the referenced route. Only the provided fields change; pass route as an empty string to clear the reference. Returns the updated node.';
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
                    'description' => 'LlmMenu node id',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Entry label',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Entry description shown in llms.txt',
                ],
                'route' => [
                    'type' => 'string',
                    'description' => 'Route (dcore-XXX) of the referenced Content or Tag; empty string clears it',
                ],
            ],
            'required' => [
                'id',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $id, ?string $name = null, ?string $description = null, ?string $route = null): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->llmMenuService->update($id, $name, $description, $route);
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
