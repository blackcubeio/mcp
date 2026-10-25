<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dcore\Data\ActiveQueryPaginator;
use Blackcube\Dcore\Helpers\Element;
use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\Host;
use Blackcube\Dcore\Models\Slug;
use Blackcube\Dcore\Models\Tag;
use Blackcube\Mcp\Models\Forms\SlugForm;
use RuntimeException;

/**
 * Read and write logic for the slugs (URLs). Element slugs (carried by a
 * Content or a Tag) are managed through content.update/tag.update: here they
 * are listed, toggled and never deleted; the creation/deletion writes only
 * apply to autonomous REDIRECT slugs (targetUrl + httpCode), cheap to
 * rebuild. Returns plain data; raises a RuntimeException with a clear
 * message on a business error.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class SlugService
{
    /**
     * @var array<int, string>|null
     */
    private ?array $hostNames = null;

    /**
     * @return array<string, mixed>
     */
    public function list(?int $hostId, ?string $kind, int $page, int $pageSize): array
    {
        $slugsQuery = Slug::query()->orderBy([
            'id' => SORT_DESC,
        ]);
        if ($hostId !== null) {
            $slugsQuery->andWhere([
                'hostId' => $hostId,
            ]);
        }
        if ($kind !== null) {
            $this->applyKind($slugsQuery, $kind);
        }

        $paginator = (new ActiveQueryPaginator($slugsQuery))
            ->withPageSize(min(max($pageSize, 1), 100))
            ->withCurrentPage(max($page, 1));

        $slugs = [];
        /** @var Slug $slug */
        foreach ($paginator->read() as $slug) {
            $slugs[] = $slug;
        }

        $elementRefs = $this->elementRefs($slugs);
        $items = [];
        foreach ($slugs as $slug) {
            $items[] = $this->item($slug, $elementRefs[$slug->getId()] ?? null);
        }

        return [
            'items' => $items,
            'total' => $paginator->getTotalItems(),
            'page' => $paginator->getCurrentPage(),
            'pageSize' => $paginator->getPageSize(),
            'totalPages' => $paginator->getTotalPages(),
            'hasMore' => $paginator->isOnLastPage() === false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        $slug = Slug::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($slug === null) {
            throw new RuntimeException('Slug not found: ' . $id);
        }

        return $this->item($slug, $this->elementRefs([
            $slug,
        ])[$slug->getId()] ?? null);
    }

    /**
     * Creates an autonomous REDIRECT slug.
     *
     * @return array<string, mixed>
     */
    public function create(string $path, string $targetUrl, int $httpCode, int $hostId, bool $active): array
    {
        $form = new SlugForm();
        $form->setScenario('redirect');
        $form->load([
            'hostId' => $hostId,
            'path' => $path,
            'targetUrl' => $targetUrl,
            'httpCode' => $httpCode,
            'active' => $active,
        ], '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }
        $slug = new Slug();
        $form->populateModel($slug);
        $slug->save();

        return $this->item($slug, null);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, ?string $path, ?string $targetUrl, ?int $httpCode, ?int $hostId, ?bool $active): array
    {
        $slug = Slug::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($slug === null) {
            throw new RuntimeException('Slug not found: ' . $id);
        }
        $element = $slug->getElement();
        if ($element !== null && ($targetUrl !== null || $httpCode !== null)) {
            throw new RuntimeException('This slug is carried by an element: it cannot become a redirect. Manage it through content.update/tag.update.');
        }

        $provided = [];
        if ($path !== null) {
            $provided['path'] = $path;
        }
        if ($targetUrl !== null) {
            $provided['targetUrl'] = $targetUrl;
        }
        if ($httpCode !== null) {
            $provided['httpCode'] = $httpCode;
        }
        if ($hostId !== null) {
            $provided['hostId'] = $hostId;
        }
        if ($active !== null) {
            $provided['active'] = $active;
        }
        $form = new SlugForm();
        $form->setId($slug->getId());
        $form->setScenario($element === null ? 'redirect' : 'edit');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }
        $form->populateModel($slug);
        $slug->save();

        return $this->item($slug, $this->elementRefs([
            $slug,
        ])[$slug->getId()] ?? null);
    }

    /**
     * Deletes an autonomous slug (redirect or orphan). A slug carried by a
     * Content or a Tag is a key element part and is never deleted here.
     *
     * @return array<string, mixed>
     */
    public function delete(int $id): array
    {
        $slug = Slug::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($slug === null) {
            throw new RuntimeException('Slug not found: ' . $id);
        }
        $element = $slug->getElement();
        if ($element !== null) {
            throw new RuntimeException('This slug is carried by an element: disable it or manage it through content.update/tag.update.');
        }
        $deleted = $this->item($slug, null);
        $slug->delete();

        return $deleted;
    }

    /**
     * Applies the kind filter: redirect (targetUrl set), element (carried by
     * a Content or a Tag), orphan (neither). Element membership goes through
     * IN sub-queries, never a join.
     */
    private function applyKind(object $slugsQuery, string $kind): void
    {
        $contentSlugIdsQuery = Content::query()->andWhere([
            'not',
            [
                'slugId' => null,
            ],
        ])->select([
            'slugId',
        ]);
        $tagSlugIdsQuery = Tag::query()->andWhere([
            'not',
            [
                'slugId' => null,
            ],
        ])->select([
            'slugId',
        ]);
        match ($kind) {
            'redirect' => $slugsQuery->andWhere([
                'not',
                [
                    'targetUrl' => null,
                ],
            ]),
            'element' => $slugsQuery->andWhere([
                'or',
                [
                    'in',
                    'id',
                    $contentSlugIdsQuery,
                ],
                [
                    'in',
                    'id',
                    $tagSlugIdsQuery,
                ],
            ]),
            'orphan' => $slugsQuery
                ->andWhere([
                    'targetUrl' => null,
                ])
                ->andWhere([
                    'not in',
                    'id',
                    $contentSlugIdsQuery,
                ])
                ->andWhere([
                    'not in',
                    'id',
                    $tagSlugIdsQuery,
                ]),
            default => throw new RuntimeException('Invalid kind "' . $kind . '" (allowed: element, redirect, orphan)'),
        };
    }

    /**
     * Element refs ({route, name}) for a page of slugs, in two queries.
     *
     * @param Slug[] $slugs
     * @return array<int, array<string, mixed>>
     */
    private function elementRefs(array $slugs): array
    {
        $refs = [];
        $slugIds = [];
        foreach ($slugs as $slug) {
            $slugIds[] = $slug->getId();
        }
        if (empty($slugIds) === false) {
            $contentsQuery = Content::query()->andWhere([
                'in',
                'slugId',
                $slugIds,
            ]);
            /** @var Content $content */
            foreach ($contentsQuery->each() as $content) {
                $refs[$content->getSlugId()] = [
                    'route' => Element::mapToRoute([
                        'type' => 'content',
                        'id' => $content->getId(),
                    ]),
                    'name' => $content->getName(),
                ];
            }
            $tagsQuery = Tag::query()->andWhere([
                'in',
                'slugId',
                $slugIds,
            ]);
            /** @var Tag $tag */
            foreach ($tagsQuery->each() as $tag) {
                $refs[$tag->getSlugId()] = [
                    'route' => Element::mapToRoute([
                        'type' => 'tag',
                        'id' => $tag->getId(),
                    ]),
                    'name' => $tag->getName(),
                ];
            }
        }

        return $refs;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function hostRef(int $hostId): ?array
    {
        if ($this->hostNames === null) {
            $this->hostNames = [];
            $hostsQuery = Host::query();
            /** @var Host $host */
            foreach ($hostsQuery->each() as $host) {
                $this->hostNames[$host->getId()] = $host->getName();
            }
        }

        return [
            'id' => $hostId,
            'name' => $this->hostNames[$hostId] ?? null,
        ];
    }

    /**
     * @param array<string, mixed>|null $elementRef
     * @return array<string, mixed>
     */
    private function item(Slug $slug, ?array $elementRef): array
    {
        if ($slug->getTargetUrl() !== null) {
            $kind = 'redirect';
        } elseif ($elementRef !== null) {
            $kind = 'element';
        } else {
            $kind = 'orphan';
        }

        return [
            'id' => $slug->getId(),
            'host' => $this->hostRef($slug->getHostId()),
            'path' => $slug->getPath(),
            'targetUrl' => $slug->getTargetUrl(),
            'httpCode' => $slug->getHttpCode(),
            'active' => $slug->isActive(),
            'element' => $elementRef,
            'kind' => $kind,
        ];
    }
}
