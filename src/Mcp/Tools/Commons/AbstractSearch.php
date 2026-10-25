<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Commons;

use Blackcube\Mcp\Services\ElementService;

/**
 * Full-text search of element nodes ranked by relevance, paginated, shared by
 * content and tag. A concrete tool sets only NAME and TYPE.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractSearch
{
    protected const TYPE = '';

    public function __construct(
        protected ElementService $elementService,
    ) {}

    public static function description(): string
    {
        return 'Full-text search of ' . static::TYPE . ' nodes ranked by relevance (name, elastic fields, blocs). Paginated.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Search terms',
                ],
                'page' => [
                    'type' => 'integer',
                    'description' => 'Page number, 1-based (default 1)',
                ],
                'pageSize' => [
                    'type' => 'integer',
                    'description' => 'Items per page, 1-100 (default 20)',
                ],
            ],
            'required' => [
                'query',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(string $query, int $page = 1, int $pageSize = 20): array
    {
        return $this->elementService->search(static::TYPE, $query, $page, $pageSize);
    }
}
