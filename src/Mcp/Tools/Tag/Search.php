<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Tag;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractSearch;

/**
 * tag.search - Full-text search of tag nodes ranked by relevance, paginated.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Search extends AbstractSearch
{
    public const NAME = 'tag.search';
    public const PERMISSIONS = [
        Rbac::PERMISSION_TAG_VIEW,
    ];
    protected const TYPE = 'tag';
}
