<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Helpers;

/**
 * Generates MCP key tokens and resolves their stored hash.
 *
 * The plaintext token is shown once at creation; only its SHA-256 hash is
 * stored (the mcpKeys hash column).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ApiKeyToken
{
    /**
     * @return array{token: string, hash: string}
     */
    public static function generate(): array
    {
        $token = bin2hex(random_bytes(32));

        return [
            'token' => $token,
            'hash' => self::hash($token),
        ];
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
