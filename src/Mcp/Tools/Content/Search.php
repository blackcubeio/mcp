<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Content;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractSearch;

/**
 * content.search - Full-text search of content nodes ranked by relevance, paginated.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Search extends AbstractSearch
{
    public const NAME = 'content.search';
    public const PERMISSIONS = [
        Rbac::PERMISSION_CONTENT_VIEW,
    ];
    protected const TYPE = 'content';
}
