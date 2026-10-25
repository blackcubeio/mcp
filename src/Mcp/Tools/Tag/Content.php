<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Tag;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\ElementService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * tag.content - Set the contents attached to a tag (full replace via the ContentTag pivot).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Content
{
    public const NAME = 'tag.content';
    public const PERMISSIONS = [
        Rbac::PERMISSION_TAG_UPDATE,
    ];

    public function __construct(
        private readonly ElementService $elementService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Set the contents attached to a tag, replacing the whole list: pass the contents to attach, each by its route; [] detaches all. Only a level-2 tag can hold contents (not a group). Resolve content routes via content.list/content.search. Returns the updated tag node.';
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
                    'description' => 'Tag route (dcore-XXX), a level-2 tag',
                ],
                'contents' => [
                    'type' => 'array',
                    'description' => 'Contents to attach (replaces the whole list; [] detaches all)',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'route' => [
                                'type' => 'string',
                                'description' => 'Content route (dcore-XXX)',
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Optional, ignored (lets you echo back tag.get)',
                            ],
                        ],
                        'required' => [
                            'route',
                        ],
                    ],
                ],
            ],
            'required' => [
                'route',
                'contents',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $contents
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $route, array $contents): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->elementService->setTagContents($route, $contents);
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
