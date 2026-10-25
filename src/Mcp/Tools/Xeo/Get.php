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
 * xeo.get - SEO/GEO metadata (xeo) + sitemap entry of a content or tag, via its slug.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Get
{
    public const NAME = 'xeo.get';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_VIEW,
        Rbac::PERMISSION_TAG_VIEW,
    ];

    public function __construct(
        private readonly ElementService $elementService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Get the SEO/GEO metadata (xeo) and the sitemap entry of a content or tag by its route (dcore-XXX). Both are carried by the element slug; each is null when absent. They are written through content.update/tag.update ({xeo: ...}, {sitemap: ...}).';
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
            $toolResult = $this->elementService->getXeo($route);
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
