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
 * globalXeo.set - Create or update the site-wide SEO entry of a host.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Set
{
    public const NAME = 'globalXeo.set';
    public const PERMISSIONS = [
        Rbac::PERMISSION_XEO_UPDATE,
    ];

    public function __construct(
        private readonly GlobalXeoService $globalXeoService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create or update the site-wide SEO/GEO entry of a host for a kind (auto-created on first set, like dboard). data is validated by the builtin schema of the kind: read it first with elasticSchema.list (kind=xeo) or globalXeo.get. Only the provided fields change. Returns the entry.';
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
                'name' => [
                    'type' => 'string',
                    'description' => 'Entry label',
                ],
                'data' => [
                    'type' => 'object',
                    'description' => 'Dynamic fields, validated by the builtin schema of the kind',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Whether the entry is emitted',
                ],
            ],
            'required' => [
                'hostId',
                'kind',
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $hostId, string $kind, ?string $name = null, ?array $data = null, ?bool $active = null): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->globalXeoService->set($hostId, $kind, $name, $data, $active);
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
