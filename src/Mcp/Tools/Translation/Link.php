<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Translation;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\TranslationService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * translation.link - Link two elements as language variants.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Link
{
    public const NAME = 'translation.link';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_UPDATE,
        Rbac::PERMISSION_TAG_UPDATE,
    ];

    public function __construct(
        private readonly TranslationService $translationService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Link two elements of the same type (content with content, tag with tag) as language variants of the same translation group. Refused when the languages match, the tree levels differ or both already belong to a group. Returns the resulting group.';
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
                    'description' => 'Element route (dcore-XXX)',
                ],
                'targetRoute' => [
                    'type' => 'string',
                    'description' => 'Route of the element to link as a variant',
                ],
            ],
            'required' => [
                'route',
                'targetRoute',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $route, string $targetRoute): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->translationService->link($route, $targetRoute);
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
