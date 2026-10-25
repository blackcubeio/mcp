<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\LlmMenu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractTreeGet;
use Blackcube\Mcp\Services\LlmMenuService;
use Psr\Log\LoggerInterface;

/**
 * llmMenu.get - Get a llms.txt navigation node by its id.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Get extends AbstractTreeGet
{
    public const NAME = 'llmMenu.get';
    public const PERMISSIONS = [
        Rbac::PERMISSION_LLMMENU_VIEW,
    ];
    protected const TYPE = 'llmMenu';

    public function __construct(
        LlmMenuService $llmMenuService,
        LoggerInterface $logger,
    ) {
        parent::__construct(
            treeService: $llmMenuService,
            logger: $logger,
        );
    }

    public static function description(): string
    {
        return 'Get a llms.txt navigation node by its id. target carries the route of the referenced Content or Tag, null for a plain heading.';
    }
}
