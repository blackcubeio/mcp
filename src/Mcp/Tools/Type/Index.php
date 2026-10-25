<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Type;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\TypeService;

/**
 * type.list - List the Types (page types of contents/tags).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'type.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_TYPE_VIEW,
    ];

    public function __construct(
        private readonly TypeService $typeService,
    ) {}

    public static function description(): string
    {
        return 'List the Types (page types of contents/tags): each maps to a render handler and declares which ElasticSchemas its blocs may use (elasticSchemas, as {id, name} refs). Resolves the typeId carried by content nodes.';
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
        return $this->typeService->list();
    }
}
