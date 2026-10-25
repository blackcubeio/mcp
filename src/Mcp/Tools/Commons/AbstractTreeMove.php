<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Commons;

use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Mcp\Interfaces\TreeServiceInterface;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Repositions a navigation tree node (and its subtree) relative to another
 * node, shared by menu and llmMenu. A concrete tool sets NAME and TYPE,
 * injects its tree service and describes itself.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractTreeMove
{
    protected const TYPE = '';

    public function __construct(
        protected TreeServiceInterface $treeService,
        protected LoggerInterface $logger,
    ) {}

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
                    'description' => ucfirst(static::TYPE) . ' id to move',
                ],
                'targetId' => [
                    'type' => 'integer',
                    'description' => 'Reference node id',
                ],
                'mode' => [
                    'type' => 'string',
                    'enum' => [
                        TreePosition::Before->value,
                        TreePosition::Into->value,
                        TreePosition::After->value,
                    ],
                    'description' => 'into = child of target; before/after = sibling',
                ],
            ],
            'required' => [
                'id',
                'targetId',
                'mode',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $id, int $targetId, string $mode): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->treeService->move($id, $targetId, $mode);
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
