<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\LlmMenu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Mcp\Services\LlmMenuService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * llmMenu.create - Create a llms.txt navigation node.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'llmMenu.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_LLMMENU_CREATE,
    ];

    public function __construct(
        private readonly LlmMenuService $llmMenuService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create a llms.txt navigation node. Without targetId it becomes a root; with targetId + mode (into = child, before/after = sibling) it is placed relative to that node, capped at 3 levels (root, category, data). route references the Content or Tag the entry links to. Returns the created node.';
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
                    'description' => 'Entry label',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Entry description shown in llms.txt',
                ],
                'route' => [
                    'type' => 'string',
                    'description' => 'Route (dcore-XXX) of the Content or Tag the entry references; omit for a plain heading',
                ],
                'targetId' => [
                    'type' => 'integer',
                    'description' => 'Reference node id for placement; omit for a root',
                ],
                'mode' => [
                    'type' => 'string',
                    'enum' => [
                        TreePosition::Before->value,
                        TreePosition::Into->value,
                        TreePosition::After->value,
                    ],
                    'description' => 'Placement relative to targetId (default into)',
                ],
            ],
            'required' => [
                'name',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $name, ?string $description = null, ?string $route = null, ?int $targetId = null, string $mode = TreePosition::Into->value): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->llmMenuService->create($name, $description, $route, $targetId, $mode);
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
