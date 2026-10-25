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
 * slug.get - Get a slug by its id.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Get
{
    public const NAME = 'slug.get';
    public const PERMISSIONS = [
        Rbac::PERMISSION_SLUG_VIEW,
    ];

    public function __construct(
        private readonly SlugService $slugService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Get a slug by its id: host, path, redirect data (targetUrl/httpCode) and the element (route) carrying it, when any.';
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
            ],
            'required' => [
                'id',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(int $id): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->slugService->get($id);
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
