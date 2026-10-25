<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Server;

use Mcp\Server\Session\SessionStoreInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Uid\Uuid;

/**
 * MCP session store backed by the host PSR-16 cache.
 *
 * Persists sessions across PHP-FPM requests when a cache is configured (e.g.
 * Redis); expiration is handled by the cache TTL.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class CacheSessionStore implements SessionStoreInterface
{
    private const PREFIX = 'mcp.session.';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $ttl = 3600,
    ) {}

    public function exists(Uuid $id): bool
    {
        return $this->cache->has($this->key($id));
    }

    public function read(Uuid $id): string|false
    {
        $sessionData = $this->cache->get($this->key($id));

        return is_string($sessionData) === true ? $sessionData : false;
    }

    public function write(Uuid $id, string $sessionData): bool
    {
        return $this->cache->set($this->key($id), $sessionData, $this->ttl);
    }

    public function destroy(Uuid $id): bool
    {
        return $this->cache->delete($this->key($id));
    }

    /**
     * @return Uuid[]
     */
    public function gc(): array
    {
        return [];
    }

    private function key(Uuid $id): string
    {
        return self::PREFIX . $id->toRfc4122();
    }
}
