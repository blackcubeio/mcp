<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Author;

use Blackcube\Dcore\Models\Author;
use Blackcube\Dboard\Components\Rbac;

/**
 * author.list - List the authors (attachable to contents and tags).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'author.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_AUTHOR_VIEW,
    ];

    public static function description(): string
    {
        return 'List the authors (firstname/lastname plus schema.org profile fields), attachable to contents and tags. Resolves the authors attached to content and tag nodes.';
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
        $authorsQuery = Author::query()->orderBy([
            'lastname' => SORT_ASC,
            'firstname' => SORT_ASC,
            'id' => SORT_ASC,
        ]);
        /** @var Author $author */
        foreach ($authorsQuery->each() as $author) {
            $items[] = self::item($author);
        }

        return [
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function item(Author $author): array
    {
        return [
            'id' => $author->getId(),
            'firstname' => $author->getFirstname(),
            'lastname' => $author->getLastname(),
            'email' => $author->getEmail(),
            'jobTitle' => $author->getJobTitle(),
            'worksFor' => $author->getWorksFor(),
            'knowsAbout' => $author->getKnowsAbout(),
            'sameAs' => $author->getSameAs(),
            'url' => $author->getUrl(),
            'image' => $author->getImage(),
            'active' => $author->isActive(),
        ];
    }
}
