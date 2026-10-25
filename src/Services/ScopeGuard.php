<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

/**
 * Gates a tool against the permissions granted to the current credential: a
 * tool is open when at least one of the permissions it declares is granted,
 * the rule of JwtAuthMiddleware::withRbac() in dboard.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ScopeGuard
{
    /**
     * @param string[] $grantedPermissions
     * @param string[] $requiredPermissions
     */
    public function isGranted(array $grantedPermissions, array $requiredPermissions): bool
    {
        $granted = false;
        foreach ($requiredPermissions as $permission) {
            if (in_array($permission, $grantedPermissions, true) === true) {
                $granted = true;
            }
        }
        return $granted;
    }
}
