<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Mcp\Models\Forms\HazeltreeReorderForm;
use RuntimeException;

/**
 * Shared Hazeltree reorder logic for the tree services (content/tag via
 * ElementService, menu via MenuService). Mirrors dboard AbstractHazeltreeReorder:
 * validate the parameters, forbid moving a node into its own subtree, check the
 * moved subtree depth against the max level, then save the placement. The
 * concrete service resolves the source/target nodes and the max level.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractHazeltree
{
    protected function reorder(object $source, object $target, string $mode, int $maxLevel): void
    {
        $reorderForm = new HazeltreeReorderForm();
        $reorderForm->load([
            'sourceId' => (string) $source->getId(),
            'targetId' => (string) $target->getId(),
            'position' => $mode,
        ], '');
        if ($reorderForm->validate() === false) {
            throw new RuntimeException('Invalid parameters.');
        }

        $position = TreePosition::from($reorderForm->getPosition());
        $this->validateMove($source, $target, $position, $maxLevel);

        $success = match ($position) {
            TreePosition::Before => $source->saveBefore($target),
            TreePosition::Into => $source->saveInto($target),
            TreePosition::After => $source->saveAfter($target),
        };
        if ($success === false) {
            throw new RuntimeException('Move failed.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function hazeltree(object $model): array
    {
        return [
            'path' => $model->path,
            'left' => (float) $model->left,
            'right' => (float) $model->right,
            'level' => $model->level,
        ];
    }

    private function validateMove(object $source, object $target, TreePosition $position, int $maxLevel): void
    {
        $descendants = [];
        $descendantsQuery = $source->relativeQuery()->children()->includeDescendants();
        /** @var object $descendant */
        foreach ($descendantsQuery->each() as $descendant) {
            $descendants[] = $descendant;
        }

        foreach ($descendants as $descendant) {
            if ($descendant->getId() === $target->getId()) {
                throw new RuntimeException('Cannot move an element into one of its children.');
            }
        }

        $sourceLevel = $source->getLevel();
        $maxDescendantLevel = $sourceLevel;
        foreach ($descendants as $descendant) {
            if ($descendant->getLevel() > $maxDescendantLevel) {
                $maxDescendantLevel = $descendant->getLevel();
            }
        }
        $subtreeDepth = $maxDescendantLevel - $sourceLevel;

        $newSourceLevel = match ($position) {
            TreePosition::Into => $target->getLevel() + 1,
            TreePosition::Before, TreePosition::After => $target->getLevel(),
        };

        $wouldBeMaxLevel = $newSourceLevel + $subtreeDepth;
        if ($wouldBeMaxLevel > $maxLevel) {
            throw new RuntimeException('Maximum depth of ' . $maxLevel . ' levels exceeded.');
        }
    }
}
