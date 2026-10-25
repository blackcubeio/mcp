<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Commons;

use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Mcp\Services\ElementService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Repositions an element node (and its subtree) in the tree, shared by content
 * and tag. A concrete tool sets only NAME and TYPE; the depth limit comes from
 * ElementService::MAX_LEVEL.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractMove
{
    protected const TYPE = '';

    public function __construct(
        protected ElementService $elementService,
        protected LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Reposition a ' . static::TYPE . ' node (with its subtree) relative to another '
            . static::TYPE . ' node: mode into = becomes its child, before/after = becomes a sibling. '
            . 'Refused if it would exceed ' . ElementService::MAX_LEVEL[static::TYPE] . ' levels. The route is unchanged by the move. '
            . 'Returns the moved node.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'route' => [
                    'type' => 'string',
                    'description' => ucfirst(static::TYPE) . ' route (dcore-XXX) to move',
                ],
                'targetRoute' => [
                    'type' => 'string',
                    'description' => 'Reference ' . static::TYPE . ' route (dcore-XXX)',
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
                'route',
                'targetRoute',
                'mode',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $route, string $targetRoute, string $mode): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->elementService->move(static::TYPE, $route, $targetRoute, $mode);
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
