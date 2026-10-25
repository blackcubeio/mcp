<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Dcore\Models\Host;
use Blackcube\Dcore\Models\Language;
use Blackcube\Dcore\Models\Menu;
use Blackcube\Mcp\Interfaces\TreeServiceInterface;
use Blackcube\Mcp\Models\Forms\MenuForm;
use RuntimeException;

/**
 * Read and write logic for the navigation Menu tree (a Hazeltree capped at 4
 * levels). Returns plain data; raises a RuntimeException with a clear message
 * on a business error (not found, max level, has children, unknown target),
 * which the tools translate into a CallToolResult error.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class MenuService extends AbstractHazeltree implements TreeServiceInterface
{
    private const MAX_LEVEL = 4;

    /**
     * @var array<int, string>|null
     */
    private ?array $hostNames = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $languageNames = null;

    /**
     * @return array<string, mixed>
     */
    public function list(?string $languageId, ?int $hostId): array
    {
        $menusQuery = Menu::query();
        if ($languageId !== null) {
            $menusQuery = $menusQuery->andWhere([
                'languageId' => $languageId,
            ]);
        }
        if ($hostId !== null) {
            $menusQuery = $menusQuery->andWhere([
                'hostId' => $hostId,
            ]);
        }
        $menusQuery = $menusQuery->natural();

        $items = [];
        /** @var Menu $menu */
        foreach ($menusQuery->each() as $menu) {
            $items[] = $this->item($menu);
        }

        return [
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        $menu = Menu::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($menu === null) {
            throw new RuntimeException('Menu not found: ' . $id);
        }

        return $this->item($menu);
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $name, string $languageId, ?int $hostId, ?string $route, ?string $queryString, bool $active, ?int $targetId, string $mode): array
    {
        $provided = [
            'name' => $name,
            'languageId' => $languageId,
            'active' => $active,
        ];
        if ($hostId !== null) {
            $provided['hostId'] = $hostId;
        }
        if ($route !== null) {
            $provided['route'] = $route;
        }
        if ($queryString !== null) {
            $provided['queryString'] = $queryString;
        }
        $form = new MenuForm();
        $form->setScenario('create');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }
        $menu = new Menu();
        $form->populateModel($menu);
        if ($hostId === null) {
            $menu->setHostId(null);
        }

        $this->placeNew($menu, $targetId, $mode);

        return $this->item($menu);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, ?string $name, ?string $languageId, ?int $hostId, ?string $route, ?string $queryString, ?bool $active): array
    {
        $menu = Menu::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($menu === null) {
            throw new RuntimeException('Menu not found: ' . $id);
        }
        $provided = [];
        if ($name !== null) {
            $provided['name'] = $name;
        }
        if ($languageId !== null) {
            $provided['languageId'] = $languageId;
        }
        if ($hostId !== null) {
            $provided['hostId'] = $hostId;
        }
        if ($route !== null) {
            $provided['route'] = $route;
        }
        if ($queryString !== null) {
            $provided['queryString'] = $queryString;
        }
        if ($active !== null) {
            $provided['active'] = $active;
        }
        $form = new MenuForm();
        $form->setScenario('edit');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }
        $form->populateModel($menu);
        $menu->save();

        return $this->item($menu);
    }

    /**
     * @return array<string, mixed>
     */
    public function move(int $id, int $targetId, string $mode): array
    {
        $source = Menu::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($source === null) {
            throw new RuntimeException('Menu not found: ' . $id);
        }
        $target = Menu::query()->andWhere([
            'id' => $targetId,
        ])->one();
        if ($target === null) {
            throw new RuntimeException('Menu not found: ' . $targetId);
        }
        $this->reorder($source, $target, $mode, self::MAX_LEVEL);

        return $this->item($source);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(int $id): array
    {
        $menu = Menu::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($menu === null) {
            throw new RuntimeException('Menu not found: ' . $id);
        }
        $childrenCount = (int) Menu::query()->forNode($menu)->children()->count();
        if ($childrenCount > 0) {
            throw new RuntimeException('Menu has ' . $childrenCount . ' child node(s); move or delete them first');
        }
        $deleted = $this->item($menu);
        $menu->delete();

        return $deleted;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function hostRef(?int $hostId): ?array
    {
        if ($this->hostNames === null) {
            $this->hostNames = [];
            $hostsQuery = Host::query();
            /** @var Host $host */
            foreach ($hostsQuery->each() as $host) {
                $this->hostNames[$host->getId()] = $host->getName();
            }
        }

        return $hostId === null ? null : [
            'id' => $hostId,
            'name' => $this->hostNames[$hostId] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function languageRef(string $languageId): ?array
    {
        if ($this->languageNames === null) {
            $this->languageNames = [];
            $languagesQuery = Language::query();
            /** @var Language $language */
            foreach ($languagesQuery->each() as $language) {
                $this->languageNames[$language->getId()] = $language->getName();
            }
        }

        return $languageId === '' ? null : [
            'id' => $languageId,
            'name' => $this->languageNames[$languageId] ?? null,
        ];
    }

    private function placeNew(Menu $menu, ?int $targetId, string $mode): void
    {
        if ($targetId === null) {
            $menu->save();
        } else {
            $target = Menu::query()->andWhere([
                'id' => $targetId,
            ])->one();
            if ($target === null) {
                throw new RuntimeException('Menu not found: ' . $targetId);
            }
            $position = TreePosition::tryFrom($mode);
            if ($position === null) {
                throw new RuntimeException('Invalid mode "' . $mode . '" (allowed: into, before, after)');
            }
            $wouldBeLevel = $position === TreePosition::Into ? $target->getLevel() + 1 : $target->getLevel();
            if ($wouldBeLevel > self::MAX_LEVEL) {
                throw new RuntimeException('Maximum menu level (' . self::MAX_LEVEL . ') exceeded');
            }
            $this->applyPlacement($menu, $target, $position);
        }
    }

    private function applyPlacement(Menu $menu, Menu $target, TreePosition $position): void
    {
        match ($position) {
            TreePosition::Before => $menu->saveBefore($target),
            TreePosition::Into => $menu->saveInto($target),
            TreePosition::After => $menu->saveAfter($target),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function item(Menu $menu): array
    {
        return [
            'id' => $menu->getId(),
            'name' => $menu->getName(),
            'route' => $menu->getRoute(),
            'queryString' => $menu->getQueryString(),
            'host' => $this->hostRef($menu->getHostId()),
            'language' => $this->languageRef($menu->getLanguageId()),
            'active' => $menu->isActive(),
            'hazeltree' => $this->hazeltree($menu),
        ];
    }
}
