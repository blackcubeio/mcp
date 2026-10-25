<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Info;

use Blackcube\Mcp\Dboard\McpRbac;
use Blackcube\Mcp\Services\InfoService;

/**
 * info.model - Describe the dcore content model.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Model
{
    public const NAME = 'info.model';
    public const PERMISSIONS = [
        McpRbac::PERMISSION_MCP_CONNECT,
    ];

    public function __construct(
        private readonly InfoService $infoService,
    ) {}

    public static function description(): string
    {
        return 'Describe the dcore content model: what each entity is for, its key fields, relations and rules.';
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
     * @return array<string, array<string, mixed>>
     */
    public function handle(): array
    {
        return $this->infoService->getModel();
    }
}
