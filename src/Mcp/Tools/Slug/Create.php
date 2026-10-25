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
 * slug.create - Create an autonomous REDIRECT slug.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'slug.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_SLUG_CREATE,
    ];

    public function __construct(
        private readonly SlugService $slugService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create a REDIRECT slug: requests on path answer httpCode (301/302/307/308) towards targetUrl. Element slugs are NOT created here: give the element a slug through content.update/tag.update. Returns the created slug.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'URL path of the redirect (e.g. old-page)',
                ],
                'targetUrl' => [
                    'type' => 'string',
                    'description' => 'Absolute URL the redirect answers with',
                ],
                'httpCode' => [
                    'type' => 'integer',
                    'enum' => [
                        301,
                        302,
                        307,
                        308,
                    ],
                    'description' => 'Redirection status (default 301)',
                ],
                'hostId' => [
                    'type' => 'integer',
                    'description' => 'Host scope (default 1 = every domain)',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Active (default true)',
                ],
            ],
            'required' => [
                'path',
                'targetUrl',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $path, string $targetUrl, int $httpCode = 301, int $hostId = 1, bool $active = true): array|CallToolResult
    {
        $toolResult = null;
        try {
            $toolResult = $this->slugService->create($path, $targetUrl, $httpCode, $hostId, $active);
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
