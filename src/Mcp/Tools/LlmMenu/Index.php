<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\LlmMenu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\LlmMenuService;

/**
 * llmMenu.list - List the llms.txt navigation nodes in tree order.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'llmMenu.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_LLMMENU_VIEW,
    ];

    public function __construct(
        private readonly LlmMenuService $llmMenuService,
    ) {}

    public static function description(): string
    {
        return 'List the llms.txt navigation nodes (a tree capped at 3 levels: root, category, data), in tree order. Each entry carries its level and the route of the Content or Tag it references (target), when set.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return $this->llmMenuService->list();
    }
}
