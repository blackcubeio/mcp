<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Parameter;

use Blackcube\Dcore\Models\Parameter;
use Blackcube\Dboard\Components\Rbac;

/**
 * parameter.list - List the global parameters.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'parameter.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_PARAMETER_VIEW,
    ];

    public static function description(): string
    {
        return 'List the global parameters (key/value settings addressed by domain + name, e.g. PROJECT.NAME).';
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
        $parametersQuery = Parameter::query()->orderBy([
            'domain' => SORT_ASC,
            'name' => SORT_ASC,
        ]);
        /** @var Parameter $parameter */
        foreach ($parametersQuery->each() as $parameter) {
            $items[] = [
                'domain' => $parameter->getDomain(),
                'name' => $parameter->getName(),
                'value' => $parameter->getValue(),
            ];
        }

        return [
            'items' => $items,
        ];
    }
}
