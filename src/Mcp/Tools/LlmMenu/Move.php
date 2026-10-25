<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\LlmMenu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractTreeMove;
use Blackcube\Mcp\Services\LlmMenuService;
use Psr\Log\LoggerInterface;

/**
 * llmMenu.move - Move a llms.txt navigation node in the tree.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Move extends AbstractTreeMove
{
    public const NAME = 'llmMenu.move';
    public const PERMISSIONS = [
        Rbac::PERMISSION_LLMMENU_UPDATE,
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
        return 'Move a llms.txt navigation node relative to another one: into (child), before or after (sibling). The subtree follows, capped at 3 levels. Returns the moved node.';
    }
}
