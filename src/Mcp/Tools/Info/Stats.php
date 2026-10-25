<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Info;

use Blackcube\Dcore\Models\Author;
use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dcore\Models\Host;
use Blackcube\Dcore\Models\Language;
use Blackcube\Dcore\Models\LlmMenu;
use Blackcube\Dcore\Models\Menu;
use Blackcube\Dcore\Models\Slug;
use Blackcube\Dcore\Models\Tag;
use Blackcube\Dcore\Models\Type;
use Blackcube\Mcp\Dboard\McpRbac;

/**
 * info.stats - Counts of the site entities (the dashboard figures).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Stats
{
    public const NAME = 'info.stats';
    public const PERMISSIONS = [
        McpRbac::PERMISSION_MCP_CONNECT,
    ];

    public static function description(): string
    {
        return 'Counts of the site entities, the dashboard figures: contents and tags (active / available now / inactive), slugs (redirects included), navigation, referentials. Availability means active AND within the publication window.';
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
        return [
            'contents' => [
                'availableNow' => (int) Content::query()->active()->available()->count(),
                'active' => (int) Content::query()->active()->count(),
                'inactive' => (int) Content::query()->active(active: false)->count(),
            ],
            'tags' => [
                'active' => (int) Tag::query()->active()->count(),
                'inactive' => (int) Tag::query()->active(active: false)->count(),
            ],
            'slugs' => [
                'total' => (int) Slug::query()->count(),
                'redirects' => (int) Slug::query()->andWhere([
                    'not',
                    [
                        'targetUrl' => null,
                    ],
                ])->count(),
            ],
            'menus' => (int) Menu::query()->count(),
            'llmMenus' => (int) LlmMenu::query()->count(),
            'authors' => (int) Author::query()->count(),
            'languages' => [
                'active' => (int) Language::query()->active()->count(),
                'total' => (int) Language::query()->count(),
            ],
            'hosts' => (int) Host::query()->count(),
            'types' => (int) Type::query()->count(),
            'elasticSchemas' => (int) ElasticSchema::query()->count(),
        ];
    }
}
