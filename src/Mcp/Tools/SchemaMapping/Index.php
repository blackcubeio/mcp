<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\SchemaMapping;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Services\SchemaMappingService;

/**
 * schemaMapping.list - List the schema mappings driving the xeo refresh.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index
{
    public const NAME = 'schemaMapping.list';
    public const PERMISSIONS = [
        Rbac::PERMISSION_XEO_VIEW,
    ];

    public function __construct(
        private readonly SchemaMappingService $schemaMappingService,
    ) {}

    public static function description(): string
    {
        return 'List the SchemaSchema mappings: which xeo schema is fed by which regular schema (page or bloc) and how the fields map ({"mapping": {sourceField: targetField}}). This table drives the xeo refresh.';
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
        return $this->schemaMappingService->list();
    }
}
