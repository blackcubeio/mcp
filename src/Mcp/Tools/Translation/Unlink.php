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
 * translation.unlink - Detach an element from its translation group.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Unlink
{
    public const NAME = 'translation.unlink';
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
        return 'Detach a language variant from a translation group: without targetRoute the element itself leaves its group; with targetRoute that variant is detached. Returns the resulting group of the route element.';
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
                    'description' => 'Route of the variant to detach; omit to detach the element itself',
                ],
            ],
            'required' => [
                'route',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $route, ?string $targetRoute = null): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->translationService->unlink($route, $targetRoute);
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
