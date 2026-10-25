<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Interfaces;

/**
 * Node operations of an id-addressed navigation tree (menu, llmMenu), called
 * by the shared get/move/delete tools.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
interface TreeServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array;

    /**
     * @return array<string, mixed>
     */
    public function move(int $id, int $targetId, string $mode): array;

    /**
     * @return array<string, mixed>
     */
    public function delete(int $id): array;
}
