<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Slug;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\SlugService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * slug.update - Update a slug (path, redirect data, host scope, active).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'slug.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_SLUG_UPDATE,
    ];

    public function __construct(
        private readonly SlugService $slugService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update a slug. Only the provided fields change. On an element slug, targetUrl/httpCode are refused (it cannot become a redirect); use active to disable it. Returns the updated slug.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Slug id',
                ],
                'path' => [
                    'type' => 'string',
                    'description' => 'URL path',
                ],
                'targetUrl' => [
                    'type' => 'string',
                    'description' => 'Redirect target URL (redirect slugs only)',
                ],
                'httpCode' => [
                    'type' => 'integer',
                    'enum' => [
                        301,
                        302,
                        307,
                        308,
                    ],
                    'description' => 'Redirection status (redirect slugs only)',
                ],
                'hostId' => [
                    'type' => 'integer',
                    'description' => 'Host scope (1 = every domain)',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'On/off switch',
                ],
            ],
            'required' => [
                'id',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $id, ?string $path = null, ?string $targetUrl = null, ?int $httpCode = null, ?int $hostId = null, ?bool $active = null): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->slugService->update($id, $path, $targetUrl, $httpCode, $hostId, $active);
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
