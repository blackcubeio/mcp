<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp;

/**
 * Identity of the MCP server exposed in the handshake (serverInfo).
 * Name and version are overridable through the blackcube/mcp DI params.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ServerInfo
{
    public function __construct(
        public readonly string $name = 'Blackcube MCP',
        public readonly string $version = '1.0.0',
    ) {}
}
