<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Xeo;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\ElementService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * xeo.refresh - Rebuild the xeo blocs of an element from its content.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Refresh
{
    public const NAME = 'xeo.refresh';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_UPDATE,
        Rbac::PERMISSION_TAG_UPDATE,
    ];

    public function __construct(
        private readonly ElementService $elementService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Rebuild the xeo blocs of a content or tag from its current content, driven by the schemaMapping table: existing xeo blocs are dropped, one bloc is created per mapping matching the page schema and each content bloc, prefilled through the field maps. The element must carry a slug with a xeo (write one first with content.update {xeo: ...}). Returns the number of blocs created.';
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
                    'description' => 'Content or tag route (dcore-XXX)',
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
    public function handle(string $route): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->elementService->refreshXeo($route);
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
