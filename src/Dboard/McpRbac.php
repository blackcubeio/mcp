<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Dboard;

/**
 * RBAC items contributed by the MCP addon to dboard (dboard convention).
 * RbacInitializer wires ROLE_MCP_MANAGER to every MCP:* permission.
 * MCP:CONNECT is the right to reach the server and receive its tool list; every
 * credential, key or token, needs it.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class McpRbac
{
    public const PERMISSION_MCP_CONNECT = 'MCP:CONNECT';
    public const PERMISSION_MCP_CREATE = 'MCP:CREATE';
    public const PERMISSION_MCP_DELETE = 'MCP:DELETE';
    public const PERMISSION_MCP_UPDATE = 'MCP:UPDATE';
    public const PERMISSION_MCP_VIEW = 'MCP:VIEW';
    public const ROLE_MCP_MANAGER = 'MCP:MANAGER';
}
