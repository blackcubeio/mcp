<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\LlmMenu;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Mcp\Tools\Commons\AbstractTreeDelete;
use Blackcube\Mcp\Services\LlmMenuService;
use Psr\Log\LoggerInterface;

/**
 * llmMenu.delete - Delete a llms.txt navigation node.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Delete extends AbstractTreeDelete
{
    public const NAME = 'llmMenu.delete';
    public const PERMISSIONS = [
        Rbac::PERMISSION_LLMMENU_DELETE,
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
        return 'Delete a llms.txt navigation node (refused while it still has children). Navigation entries are cheap to rebuild, unlike contents: this is why llmMenu carries a delete verb. Returns the deleted node.';
    }
}
