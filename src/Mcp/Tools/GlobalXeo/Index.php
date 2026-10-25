<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\GlobalXeo;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\GlobalXeoService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * globalXeo.list - List the site-wide SEO entries per host and kind.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'globalXeo.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_XEO_VIEW,
    ];

    public function __construct(
        private readonly GlobalXeoService $globalXeoService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'List the site-wide SEO/GEO entries: one per host and kind (Organization, WebSite, Robots, Sitemap), with their existence and active state. Read one with globalXeo.get, write with globalXeo.set.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'hostId' => [
                    'type' => 'integer',
                    'description' => 'Filter by host id',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(?int $hostId = null): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->globalXeoService->list($hostId);
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
