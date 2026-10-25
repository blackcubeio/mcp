<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Content;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\ElementService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * content.tag - Set the tags of a content (full replace via the ContentTag pivot).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Tag
{
    public const NAME = 'content.tag';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_UPDATE,
    ];

    public function __construct(
        private readonly ElementService $elementService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Set the tags of a content, replacing the whole list: pass the level-2 tags to keep (groups cannot be assigned), each by its route; [] clears all tags. Resolve tag routes via tag.list/tag.search. Returns the updated content node.';
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
                    'description' => 'Content route (dcore-XXX) to tag',
                ],
                'tags' => [
                    'type' => 'array',
                    'description' => 'Level-2 tags to assign (replaces the whole list; [] clears)',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'route' => [
                                'type' => 'string',
                                'description' => 'Tag route (dcore-XXX), a level-2 tag',
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Optional, ignored (lets you echo back content.get)',
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
                'tags',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $tags
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $route, array $tags): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->elementService->setContentTags($route, $tags);
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
