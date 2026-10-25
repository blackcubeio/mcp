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
 * globalXeo.delete - Delete the site-wide SEO entry of a host for a kind.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Delete
{
    public const NAME = 'globalXeo.delete';
    public const PERMISSIONS = [
        Rbac::PERMISSION_XEO_DELETE,
    ];

    public function __construct(
        private readonly GlobalXeoService $globalXeoService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Delete the site-wide SEO/GEO entry of a host for a kind. These entries are cheap to rebuild (globalXeo.set auto-creates), hence the delete verb. Returns the deleted entry.';
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
                    'description' => 'Host id',
                ],
                'kind' => [
                    'type' => 'string',
                    'enum' => [
                        'Organization',
                        'WebSite',
                        'Robots',
                        'Sitemap',
                    ],
                    'description' => 'Entry kind',
                ],
            ],
            'required' => [
                'hostId',
                'kind',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $hostId, string $kind): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->globalXeoService->delete($hostId, $kind);
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
