<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Dcore\Helpers\Element;
use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\LlmMenu;
use Blackcube\Dcore\Models\Tag;
use Blackcube\Mcp\Interfaces\TreeServiceInterface;
use Blackcube\Mcp\Models\Forms\LlmMenuForm;
use RuntimeException;

/**
 * Read and write logic for the LlmMenu tree (llms.txt navigation, a Hazeltree
 * capped at 3 levels: root / category / data). The referenced element travels
 * as a route (dcore-XXX) on the tool side and is stored as contentId or tagId.
 * Returns plain data; raises a RuntimeException with a clear message on a
 * business error, which the tools translate into a CallToolResult error.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class LlmMenuService extends AbstractHazeltree implements TreeServiceInterface
{
    private const MAX_LEVEL = 3;

    /**
     * @var array<int, string>|null
     */
    private ?array $contentNames = null;

    /**
     * @var array<int, string>|null
     */
    private ?array $tagNames = null;

    /**
     * @return array<string, mixed>
     */
    public function list(): array
    {
        $llmMenusQuery = LlmMenu::query()->natural();

        $items = [];
        /** @var LlmMenu $llmMenu */
        foreach ($llmMenusQuery->each() as $llmMenu) {
            $items[] = $this->item($llmMenu);
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
        $llmMenu = LlmMenu::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($llmMenu === null) {
            throw new RuntimeException('LlmMenu not found: ' . $id);
        }

        return $this->item($llmMenu);
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $name, ?string $description, ?string $route, ?int $targetId, string $mode): array
    {
        $provided = [
            'name' => $name,
        ];
        if ($description !== null) {
            $provided['description'] = $description;
        }
        $target = $this->extractElementIds($route);
        if ($target !== null) {
            $provided['contentId'] = $target['contentId'];
            $provided['tagId'] = $target['tagId'];
        }
        $form = new LlmMenuForm();
        $form->setScenario('create');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }
        $llmMenu = new LlmMenu();
        $form->populateModel($llmMenu);

        $this->placeNew($llmMenu, $targetId, $mode);

        return $this->item($llmMenu);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, ?string $name, ?string $description, ?string $route): array
    {
        $llmMenu = LlmMenu::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($llmMenu === null) {
            throw new RuntimeException('LlmMenu not found: ' . $id);
        }
        $provided = [];
        if ($name !== null) {
            $provided['name'] = $name;
        }
        if ($description !== null) {
            $provided['description'] = $description;
        }
        $target = $this->extractElementIds($route);
        if ($target !== null) {
            $provided['contentId'] = $target['contentId'];
            $provided['tagId'] = $target['tagId'];
        }
        $form = new LlmMenuForm();
        $form->setScenario('edit');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }
        $form->populateModel($llmMenu);
        if ($target !== null) {
            $llmMenu->setContentId($target['contentId']);
            $llmMenu->setTagId($target['tagId']);
        }
        $llmMenu->save();

        return $this->item($llmMenu);
    }

    /**
     * @return array<string, mixed>
     */
    public function move(int $id, int $targetId, string $mode): array
    {
        $source = LlmMenu::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($source === null) {
            throw new RuntimeException('LlmMenu not found: ' . $id);
        }
        $target = LlmMenu::query()->andWhere([
            'id' => $targetId,
        ])->one();
        if ($target === null) {
            throw new RuntimeException('LlmMenu not found: ' . $targetId);
        }
        $this->reorder($source, $target, $mode, self::MAX_LEVEL);

        return $this->item($source);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(int $id): array
    {
        $llmMenu = LlmMenu::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($llmMenu === null) {
            throw new RuntimeException('LlmMenu not found: ' . $id);
        }
        $childrenCount = (int) LlmMenu::query()->forNode($llmMenu)->children()->count();
        if ($childrenCount > 0) {
            throw new RuntimeException('LlmMenu has ' . $childrenCount . ' child node(s); move or delete them first');
        }
        $deleted = $this->item($llmMenu);
        $llmMenu->delete();

        return $deleted;
    }

    /**
     * Converts the tool-side route (dcore-XXX) into the stored contentId/tagId
     * pair. An empty string clears the target; null means "not provided".
     *
     * @return array{contentId: int|null, tagId: int|null}|null
     */
    private function extractElementIds(?string $route): ?array
    {
        $elementIds = null;
        if ($route === '') {
            $elementIds = [
                'contentId' => null,
                'tagId' => null,
            ];
        } elseif ($route !== null) {
            $element = Element::createFromRoute($route);
            if ($element === null) {
                throw new RuntimeException('Invalid route: ' . $route);
            }
            if ($element->getType() === 'content') {
                $elementIds = [
                    'contentId' => (int) $element->getId(),
                    'tagId' => null,
                ];
            } else {
                $elementIds = [
                    'contentId' => null,
                    'tagId' => (int) $element->getId(),
                ];
            }
        }

        return $elementIds;
    }

    private function placeNew(LlmMenu $llmMenu, ?int $targetId, string $mode): void
    {
        if ($targetId === null) {
            $llmMenu->save();
        } else {
            $target = LlmMenu::query()->andWhere([
                'id' => $targetId,
            ])->one();
            if ($target === null) {
                throw new RuntimeException('LlmMenu not found: ' . $targetId);
            }
            $position = TreePosition::tryFrom($mode);
            if ($position === null) {
                throw new RuntimeException('Invalid mode "' . $mode . '" (allowed: into, before, after)');
            }
            $wouldBeLevel = $position === TreePosition::Into ? $target->getLevel() + 1 : $target->getLevel();
            if ($wouldBeLevel > self::MAX_LEVEL) {
                throw new RuntimeException('Maximum llmMenu level (' . self::MAX_LEVEL . ') exceeded');
            }
            $this->applyPlacement($llmMenu, $target, $position);
        }
    }

    private function applyPlacement(LlmMenu $llmMenu, LlmMenu $target, TreePosition $position): void
    {
        match ($position) {
            TreePosition::Before => $llmMenu->saveBefore($target),
            TreePosition::Into => $llmMenu->saveInto($target),
            TreePosition::After => $llmMenu->saveAfter($target),
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function targetRef(?int $contentId, ?int $tagId): ?array
    {
        $target = null;
        if ($contentId !== null) {
            if ($this->contentNames === null) {
                $this->contentNames = [];
                $contentsQuery = Content::query();
                /** @var Content $content */
                foreach ($contentsQuery->each() as $content) {
                    $this->contentNames[$content->getId()] = $content->getName();
                }
            }

            $target = [
                'route' => Element::mapToRoute([
                    'type' => 'content',
                    'id' => $contentId,
                ]),
                'name' => $this->contentNames[$contentId] ?? null,
            ];
        } elseif ($tagId !== null) {
            if ($this->tagNames === null) {
                $this->tagNames = [];
                $tagsQuery = Tag::query();
                /** @var Tag $tag */
                foreach ($tagsQuery->each() as $tag) {
                    $this->tagNames[$tag->getId()] = $tag->getName();
                }
            }

            $target = [
                'route' => Element::mapToRoute([
                    'type' => 'tag',
                    'id' => $tagId,
                ]),
                'name' => $this->tagNames[$tagId] ?? null,
            ];
        }

        return $target;
    }

    /**
     * @return array<string, mixed>
     */
    private function item(LlmMenu $llmMenu): array
    {
        return [
            'id' => $llmMenu->getId(),
            'name' => $llmMenu->getName(),
            'description' => $llmMenu->getDescription(),
            'target' => $this->targetRef($llmMenu->getContentId(), $llmMenu->getTagId()),
            'hazeltree' => $this->hazeltree($llmMenu),
        ];
    }
}
