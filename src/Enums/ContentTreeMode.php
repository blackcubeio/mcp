<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Enums;

/**
 * Tree scope for content.list: which nodes to list relative to a reference node.
 * Roots is absolute (no reference needed); the others require a route.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
enum ContentTreeMode: string
{
    case Roots = 'roots';
    case Children = 'children';
    case Descendants = 'descendants';
    case Parent = 'parent';
    case Ancestors = 'ancestors';
    case Siblings = 'siblings';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        $modeValues = [];
        foreach (self::cases() as $mode) {
            $modeValues[] = $mode->value;
        }
        return $modeValues;
    }

    public function isRelative(): bool
    {
        return $this !== self::Roots;
    }
}
