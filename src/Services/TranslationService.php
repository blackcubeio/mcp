<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dcore\Enums\ModelKind;
use Blackcube\Dcore\Helpers\Element;
use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\Language;
use Blackcube\Dcore\Models\Tag;
use RuntimeException;

/**
 * Links and unlinks the language variants of contents and tags: two elements
 * of the same type joined through their translationGroup. The business
 * guards (same tree level, different languages, no conflicting group) live
 * in the dcore models (linkTranslation/unlinkTranslation); here the routes
 * are converted and the resulting group is returned. Raises a
 * RuntimeException with a clear message on a business error.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TranslationService
{
    /**
     * @var array<string, string>|null
     */
    private ?array $languageNames = null;

    /**
     * @return array<string, mixed>
     */
    public function link(string $route, string $targetRoute): array
    {
        $element = Element::createFromRoute($route, ModelKind::Model);
        if ($element === null) {
            throw new RuntimeException('Invalid route: ' . $route);
        }
        $model = $element->getModel();
        if ($model === null) {
            throw new RuntimeException('Element not found: ' . $route);
        }
        $targetElement = Element::createFromRoute($targetRoute, ModelKind::Model);
        if ($targetElement === null) {
            throw new RuntimeException('Invalid route: ' . $targetRoute);
        }
        $target = $targetElement->getModel();
        if ($target === null) {
            throw new RuntimeException('Element not found: ' . $targetRoute);
        }
        if ($model::class !== $target::class) {
            throw new RuntimeException('Both routes must reference the same element type (content with content, tag with tag)');
        }

        $model->linkTranslation($target->getId());

        $freshElement = Element::createFromRoute($route, ModelKind::Model);
        if ($freshElement === null) {
            throw new RuntimeException('Invalid route: ' . $route);
        }
        $freshModel = $freshElement->getModel();
        if ($freshModel === null) {
            throw new RuntimeException('Element not found: ' . $route);
        }

        return $this->group($freshModel);
    }

    /**
     * @return array<string, mixed>
     */
    public function unlink(string $route, ?string $targetRoute): array
    {
        $element = Element::createFromRoute($route, ModelKind::Model);
        if ($element === null) {
            throw new RuntimeException('Invalid route: ' . $route);
        }
        $model = $element->getModel();
        if ($model === null) {
            throw new RuntimeException('Element not found: ' . $route);
        }
        $targetId = null;
        if ($targetRoute !== null) {
            $targetElement = Element::createFromRoute($targetRoute, ModelKind::Model);
            if ($targetElement === null) {
                throw new RuntimeException('Invalid route: ' . $targetRoute);
            }
            $target = $targetElement->getModel();
            if ($target === null) {
                throw new RuntimeException('Element not found: ' . $targetRoute);
            }
            if ($model::class !== $target::class) {
                throw new RuntimeException('Both routes must reference the same element type (content with content, tag with tag)');
            }
            $targetId = $target->getId();
        }

        $model->unlinkTranslation($targetId);

        $freshElement = Element::createFromRoute($route, ModelKind::Model);
        if ($freshElement === null) {
            throw new RuntimeException('Invalid route: ' . $route);
        }
        $freshModel = $freshElement->getModel();
        if ($freshModel === null) {
            throw new RuntimeException('Element not found: ' . $route);
        }

        return $this->group($freshModel);
    }

    /**
     * The translation group of the element after the operation: the element
     * itself plus its variants, each with its route and language.
     *
     * @return array<string, mixed>
     */
    private function group(Content|Tag $model): array
    {
        $type = $model instanceof Content ? 'content' : 'tag';
        $members = [
            $model,
        ];
        if ($model->getTranslationGroupId() !== null) {
            $members = [];
            $membersQuery = $model::query()
                ->andWhere([
                    'translationGroupId' => $model->getTranslationGroupId(),
                ])
                ->orderBy([
                    'languageId' => SORT_ASC,
                ]);
            /** @var Content|Tag $member */
            foreach ($membersQuery->each() as $member) {
                $members[] = $member;
            }
        }

        $items = [];
        foreach ($members as $member) {
            $items[] = [
                'route' => Element::mapToRoute([
                    'type' => $type,
                    'id' => $member->getId(),
                ]),
                'name' => $member->getName(),
                'language' => $this->languageRef($member->getLanguageId()),
            ];
        }

        return [
            'route' => Element::mapToRoute([
                'type' => $type,
                'id' => $model->getId(),
            ]),
            'translationGroupId' => $model->getTranslationGroupId(),
            'translations' => $items,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function languageRef(?string $languageId): ?array
    {
        $languageRef = null;
        if ($languageId !== null) {
            if ($this->languageNames === null) {
                $this->languageNames = [];
                $languagesQuery = Language::query();
                /** @var Language $language */
                foreach ($languagesQuery->each() as $language) {
                    $this->languageNames[$language->getId()] = $language->getName();
                }
            }

            $languageRef = [
                'id' => $languageId,
                'name' => $this->languageNames[$languageId] ?? null,
            ];
        }

        return $languageRef;
    }
}
