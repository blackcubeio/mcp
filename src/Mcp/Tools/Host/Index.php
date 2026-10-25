<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Host;

use Blackcube\Dcore\Models\Host;
use Blackcube\Dboard\Components\Rbac;

/**
 * host.list - List the hosts (domains) served.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'host.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_HOST_VIEW,
    ];

    public static function description(): string
    {
        return 'List the hosts (domains) served. A slug whose host is "*" is served on every host; otherwise it matches a host name listed here.';
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
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $items = [];
        $hostsQuery = Host::query()->orderBy([
            'name' => SORT_ASC,
            'id' => SORT_ASC,
        ]);
        /** @var Host $host */
        foreach ($hostsQuery->each() as $host) {
            $items[] = self::item($host);
        }

        return [
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function item(Host $host): array
    {
        return [
            'id' => $host->getId(),
            'name' => $host->getName(),
            'active' => $host->isActive(),
            'siteName' => $host->getSiteName(),
            'siteAlternateName' => $host->getSiteAlternateName(),
            'siteDescription' => $host->getSiteDescription(),
        ];
    }
}
