<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Language;

use Blackcube\Dcore\Models\Language;
use Blackcube\Dboard\Components\Rbac;

/**
 * language.list - List the site languages.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'language.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_LANGUAGE_VIEW,
    ];

    public static function description(): string
    {
        return 'List the site languages (id e.g. "fr"). main flags the default language; active toggles availability. Resolves the languageId carried by content nodes.';
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
        $items = [];
        $languagesQuery = Language::query()->orderBy([
            'name' => SORT_ASC,
            'id' => SORT_ASC,
        ]);
        /** @var Language $language */
        foreach ($languagesQuery->each() as $language) {
            $items[] = self::item($language);
        }

        return [
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function item(Language $language): array
    {
        return [
            'id' => $language->getId(),
            'name' => $language->getName(),
            'main' => $language->isMain(),
            'active' => $language->isActive(),
        ];
    }
}
