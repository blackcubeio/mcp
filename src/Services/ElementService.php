<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Dcore\Data\ActiveQueryPaginator;
use Blackcube\Dcore\Helpers\Element;
use Blackcube\Dcore\Helpers\SearchHelper;
use Blackcube\Dcore\Models\Bloc;
use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\ContentTag;
use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dcore\Models\Host;
use Blackcube\Dcore\Models\Language;
use Blackcube\Dcore\Models\LlmMenu;
use Blackcube\Dcore\Models\Sitemap;
use Blackcube\Dcore\Models\Slug;
use Blackcube\Dcore\Models\Tag;
use Blackcube\Dcore\Models\Type;
use Blackcube\Dcore\Models\Xeo;
use Blackcube\Dcore\Services\FileService;
use Blackcube\Dcore\Services\XeoRefreshService;
use Blackcube\Mcp\Enums\ContentTreeMode;
use Blackcube\Mcp\Helpers\ElasticFormHelper;
use Blackcube\Mcp\Models\Forms\BlocForm;
use Blackcube\Mcp\Models\Forms\ContentForm;
use Blackcube\Mcp\Models\Forms\SitemapForm;
use Blackcube\Mcp\Models\Forms\SlugForm;
use Blackcube\Mcp\Models\Forms\TagForm;
use Blackcube\Mcp\Models\Forms\XeoForm;
use RuntimeException;
use Throwable;
use Yiisoft\ActiveRecord\ActiveQueryInterface;

/**
 * Shared read logic for routable elements (content and tag), which are
 * structurally identical (Hazeltree + Elastic blocs + slug + authors).
 * get/list/search are parametered by element type; the few per-type
 * differences (taggable, pivot table, search helper, llmMenu column) are
 * resolved internally. Returns plain data (no MCP coupling); the tools
 * translate a null result into a CallToolResult error.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ElementService extends AbstractHazeltree
{
    /**
     * @var array<string, class-string>
     */
    private const MODEL_CLASS = [
        'content' => Content::class,
        'tag' => Tag::class,
    ];

    /**
     * @var array<string, int>
     */
    public const MAX_LEVEL = [
        'content' => 99,
        'tag' => 2,
    ];

    /**
     * @var array<string, class-string>
     */
    private const FORM_CLASS = [
        'content' => ContentForm::class,
        'tag' => TagForm::class,
    ];

    public function __construct(
        private readonly ExportService $exportService,
        private readonly FileService $fileService,
        private readonly XeoRefreshService $xeoRefreshService,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function get(Element $element): ?array
    {
        $node = null;
        $modelClass = self::MODEL_CLASS[$element->getType()] ?? null;
        if ($modelClass !== null) {
            $model = $modelClass::query()->andWhere([
                'id' => $element->getId(),
            ])->with([
                'slug',
                'blocs',
            ])->one();
            if ($model !== null) {
                $node = $this->buildNode($element, $model);
            }
        }

        return $node;
    }

    /**
     * Xeo and sitemap entry of an element, both carried by its slug. Raises a
     * RuntimeException on an invalid route, a missing node or a missing slug.
     *
     * @return array<string, mixed>
     */
    public function getXeo(string $route): array
    {
        $element = Element::createFromRoute($route);
        $modelClass = null;
        if ($element !== null) {
            $modelClass = self::MODEL_CLASS[$element->getType()] ?? null;
        }
        if ($modelClass === null) {
            throw new RuntimeException('Invalid route: ' . $route);
        }
        $model = $modelClass::query()->andWhere([
            'id' => $element->getId(),
        ])->one();
        if ($model === null) {
            throw new RuntimeException('Element not found: ' . $route);
        }
        $slug = $model->getSlugQuery()->one();
        if ($slug === null) {
            throw new RuntimeException('Element has no slug (non-routable): ' . $route);
        }
        $exported = $this->exportService->exportObject($slug);

        return [
            'route' => $element->toRoute(),
            'xeo' => $exported['xeo'] ?? null,
            'sitemap' => $exported['sitemap'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function list(string $type, ?ContentTreeMode $mode, ?string $route, int $page, int $pageSize): ?array
    {
        $listing = null;
        $modelClass = self::MODEL_CLASS[$type] ?? null;
        if ($modelClass !== null) {
            $treeQuery = $this->treeQuery($modelClass, $type, $mode, $route);
            if ($treeQuery !== null) {
                $listing = $this->paginate($type, $treeQuery, $page, $pageSize, false);
            }
        }

        return $listing;
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $type, string $query, int $page, int $pageSize): array
    {
        $searchQuery = $type === 'content'
            ? SearchHelper::contentQuery($query, Content::class)
            : SearchHelper::tagQuery($query, Tag::class);

        return $this->paginate($type, $searchQuery, $page, $pageSize, true);
    }

    /**
     * Creates a bare element (simple fields only, no data/blocs/slug/tags/authors),
     * validated by its form, and places it in the tree. Raises a RuntimeException
     * on a validation or placement error.
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>|null
     */
    public function create(string $type, array $fields, ?string $targetRoute, string $mode): ?array
    {
        $node = null;
        $modelClass = self::MODEL_CLASS[$type] ?? null;
        if ($modelClass !== null) {
            $formClass = self::FORM_CLASS[$type];
            $elementForm = new $formClass();
            $elementForm->setScenario('create');
            $elementForm->load($fields, '');
            if ($elementForm->validate() === false) {
                throw new RuntimeException(implode('; ', $elementForm->getValidationResult()->getErrorMessages()));
            }
            $model = new $modelClass();
            $elementForm->populateModel($model);
            $this->place($model, $type, $targetRoute, $mode);
            $fresh = $modelClass::query()->andWhere([
                'id' => $model->getId(),
            ])->one();
            $node = $this->buildNode(Element::createFromModel($fresh), $fresh);
        }

        return $node;
    }

    /**
     * Full update of an element in a single transaction, mirroring the dboard
     * save sequence: a) content fields, b) node elastic data, c) slug,
     * d) sitemap, e) xeo (+ xeo blocs), f) content blocs. Each section is
     * applied only when present in the payload. Any validation/save failure
     * rolls the whole transaction back.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public function update(string $type, string $route, array $payload): ?array
    {
        $node = null;
        $modelClass = self::MODEL_CLASS[$type] ?? null;
        if ($modelClass !== null) {
            $element = Element::createFromRoute($route);
            if ($element === null || $element->getType() !== $type) {
                throw new RuntimeException('Invalid route for ' . $type . ': ' . $route);
            }
            $model = $modelClass::query()->andWhere([
                'id' => $element->getId(),
            ])->one();
            if ($model === null) {
                throw new RuntimeException('Node not found for ' . $type . ': ' . $route);
            }

            $formClass = self::FORM_CLASS[$type];
            $transaction = $model->db()->beginTransaction();
            try {
                if (isset($payload['content']) === true) {
                    $this->saveContentFields($formClass, $model, $payload['content']);
                }
                if (isset($payload['data']) === true) {
                    ElasticFormHelper::apply($formClass, $model, $payload['data']);
                    $model->save();
                    $this->fileService->processEntityFiles($model);
                }
                $slug = null;
                if (isset($payload['slug']) === true) {
                    $slug = $this->saveSlug($model, $payload['slug']);
                }
                if (isset($payload['sitemap']) === true) {
                    $slug = $slug ?? $model->getSlugQuery()->one();
                    if ($slug === null) {
                        throw new RuntimeException('Cannot set sitemap: the ' . $type . ' has no slug.');
                    }
                    $this->saveSitemap($slug, $payload['sitemap']);
                }
                if (isset($payload['xeo']) === true) {
                    $slug = $slug ?? $model->getSlugQuery()->one();
                    if ($slug === null) {
                        throw new RuntimeException('Cannot set xeo: the ' . $type . ' has no slug.');
                    }
                    $this->saveXeo($model, $slug, $payload['xeo']);
                }
                if (isset($payload['blocs']) === true) {
                    $this->syncBlocs($model, $payload['blocs'], $model);
                }
                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

            $fresh = $modelClass::query()->andWhere([
                'id' => $model->getId(),
            ])->one();
            $node = $this->buildNode(Element::createFromRoute($route), $fresh);
        }

        return $node;
    }

    /**
     * Rebuilds the xeo blocs of an element from its content. Raises a
     * RuntimeException on an invalid route, a missing node, a missing slug or
     * a missing xeo.
     *
     * @return array<string, mixed>
     */
    public function refreshXeo(string $route): array
    {
        $element = Element::createFromRoute($route);
        $modelClass = null;
        if ($element !== null) {
            $modelClass = self::MODEL_CLASS[$element->getType()] ?? null;
        }
        if ($modelClass === null) {
            throw new RuntimeException('Invalid route: ' . $route);
        }
        $model = $modelClass::query()->andWhere([
            'id' => $element->getId(),
        ])->one();
        if ($model === null) {
            throw new RuntimeException('Element not found: ' . $route);
        }
        $slug = $model->getSlugQuery()->one();
        if ($slug === null) {
            throw new RuntimeException('Element has no slug (non-routable): ' . $route);
        }
        $xeo = $slug->getXeoQuery()->one();
        if ($xeo === null) {
            throw new RuntimeException('Element has no xeo yet: write one first with content.update/tag.update ({xeo: ...})');
        }

        return [
            'route' => $element->toRoute(),
            'blocsCreated' => $this->xeoRefreshService->refresh($model, $xeo),
        ];
    }

    /**
     * @param class-string $formClass
     * @param array<string, mixed> $fields
     */
    private function saveContentFields(string $formClass, object $model, array $fields): void
    {
        $elementForm = new $formClass();
        $elementForm->setScenario('edit');
        $elementForm->setPatchFields(array_keys($fields));
        $elementForm->load($fields, '');
        if ($elementForm->validate() === false) {
            throw new RuntimeException(implode('; ', $elementForm->getValidationResult()->getErrorMessages()));
        }
        $elementForm->populateModel($model);
        $model->save();
    }

    /**
     * @param array<string, mixed> $slugData
     */
    private function saveSlug(object $model, array $slugData): Slug
    {
        $slug = $model->getSlugQuery()->one();
        $hasTarget = ($slugData['targetUrl'] ?? null) !== null && ($slugData['targetUrl'] ?? '') !== '';
        $slugForm = new SlugForm();
        if ($slug !== null) {
            $slugForm->setId($slug->getId());
            $slugForm->setScenario($hasTarget === true ? 'redirect' : 'edit');
        } else {
            $slug = new Slug();
            $slugForm->setScenario($hasTarget === true ? 'redirect' : 'create');
        }
        $slugForm->setPatchFields(array_keys($slugData));
        $slugForm->load($slugData, '');
        if ($slugForm->validate() === false) {
            throw new RuntimeException(implode('; ', $slugForm->getValidationResult()->getErrorMessages()));
        }
        $slugForm->populateModel($slug);
        $slug->save();

        if ($model->getSlugId() !== $slug->getId()) {
            $model->setSlugId($slug->getId());
            $model->save();
        }

        $xeo = $slug->getXeoQuery()->one();
        if ($xeo === null) {
            $xeo = new Xeo();
            $xeo->setSlugId($slug->getId());
            $xeo->save();
        }

        return $slug;
    }

    /**
     * @param array<string, mixed> $sitemapData
     */
    private function saveSitemap(Slug $slug, array $sitemapData): void
    {
        $sitemap = $slug->getSitemapQuery()->one();
        $sitemapForm = new SitemapForm();
        if ($sitemap !== null) {
            $sitemapForm->setScenario('edit');
        } else {
            $sitemap = new Sitemap();
            $sitemapForm->setScenario('create');
        }
        $sitemapForm->setPatchFields(array_keys($sitemapData));
        $sitemapForm->load($sitemapData, '');
        if ($sitemapForm->validate() === false) {
            throw new RuntimeException(implode('; ', $sitemapForm->getValidationResult()->getErrorMessages()));
        }
        $sitemap->setSlugId($slug->getId());
        $sitemapForm->populateModel($sitemap);
        $sitemap->save();
    }

    /**
     * The canonical flag (as xeo.get returns it) is translated into canonicalSlugId:
     * the element slug itself when true, none when false.
     *
     * @param array<string, mixed> $xeoData
     */
    private function saveXeo(object $model, Slug $slug, array $xeoData): void
    {
        $blocs = $xeoData['blocs'] ?? null;
        unset($xeoData['blocs']);
        if (isset($xeoData['canonical']) === true) {
            $xeoData['canonicalSlugId'] = null;
            if ($xeoData['canonical'] === true) {
                $xeoData['canonicalSlugId'] = $slug->getId();
            }
            unset($xeoData['canonical']);
        }

        $xeo = $slug->getXeoQuery()->one();
        $xeoForm = new XeoForm();
        if ($xeo !== null) {
            $xeoForm->setScenario('edit');
        } else {
            $xeo = new Xeo();
            $xeo->setSlugId($slug->getId());
            $xeoForm->setScenario('create');
        }
        $xeoForm->setPatchFields(array_keys($xeoData));
        $xeoForm->load($xeoData, '');
        if ($xeoForm->validate() === false) {
            throw new RuntimeException(implode('; ', $xeoForm->getValidationResult()->getErrorMessages()));
        }
        $xeoForm->populateModel($xeo);
        $xeo->save();

        $basePath = FileService::buildEntityPath($model) . '/' . FileService::buildEntityPath($xeo);
        $this->fileService->processRegularFiles($xeo, [
            'image',
        ], $basePath);
        $xeo->save();

        if (is_array($blocs) === true) {
            $this->syncBlocs($xeo, $blocs, $model);
        }
    }

    /**
     * Declarative bloc sync, shared by content, tag and xeo (all expose
     * attachBloc/detachBloc/moveBloc/getBlocsQuery via OrderedManagementTrait):
     * create the missing ones, update the existing ones, delete the dropped ones,
     * then reorder to match the payload.
     *
     * @param object $model The bloc bearer (Content, Tag or Xeo model).
     * @param array<int, array<string, mixed>> $blocs
     * @param object $fileParent The entity used to build the bloc file paths.
     */
    private function syncBlocs(object $model, array $blocs, object $fileParent): void
    {
        $payloadIds = [];
        foreach ($blocs as $blocData) {
            if (isset($blocData['id']) === true) {
                $payloadIds[] = (int) $blocData['id'];
            }
        }

        $toDelete = [];
        $blocsQuery = $model->getBlocsQuery();
        /** @var Bloc $existing */
        foreach ($blocsQuery->each() as $existing) {
            if (in_array($existing->getId(), $payloadIds, true) === false) {
                $toDelete[] = $existing;
            }
        }
        foreach ($toDelete as $existing) {
            $this->fileService->deleteBlocFiles($existing, $fileParent);
            $model->detachBloc($existing);
        }

        $ordered = [];
        foreach ($blocs as $blocData) {
            $bloc = null;
            if (isset($blocData['id']) === true) {
                $bloc = Bloc::query()->andWhere([
                    'id' => (int) $blocData['id'],
                ])->one();
                if ($bloc === null) {
                    throw new RuntimeException('Bloc not found: ' . $blocData['id']);
                }
            }
            if ($bloc === null) {
                $bloc = $this->newBloc($blocData);
                $model->attachBloc($bloc, 0);
            }
            $this->saveBlocBody($fileParent, $bloc, $blocData);
            $ordered[] = $bloc;
        }

        $position = 1;
        foreach ($ordered as $bloc) {
            $model->moveBloc($bloc, $position);
            $position++;
        }
    }

    /**
     * f1: presave a new bloc inactive so it gets an id (needed by the file path).
     *
     * @param array<string, mixed> $blocData
     */
    private function newBloc(array $blocData): Bloc
    {
        $schemaId = $blocData['elasticSchemaId'] ?? null;
        if ($schemaId === null) {
            throw new RuntimeException('A new bloc requires an elasticSchemaId.');
        }
        $bloc = new Bloc();
        $bloc->setElasticSchemaId((int) $schemaId);
        $bloc->setActive(false);
        $bloc->save();

        return $bloc;
    }

    /**
     * f2 + f3: populate the bloc body, activate, save, then process its files
     * (now that the bloc has an id) and purge files removed since the start.
     *
     * @param array<string, mixed> $blocData
     */
    private function saveBlocBody(object $model, Bloc $bloc, array $blocData): void
    {
        $initialFileValues = $this->fileService->extractFileValues($bloc);

        $blocForm = new BlocForm();
        $blocForm->initFromModel($bloc);
        $blocForm->setScenario('edit');
        $body = array_merge(
            $blocData['data'] ?? [],
            [
                'active' => $blocData['active'] ?? true,
            ],
        );
        $blocForm->load($body, '');
        if ($blocForm->validate() === false) {
            throw new RuntimeException(implode('; ', $blocForm->getValidationResult()->getErrorMessages()));
        }
        $blocForm->populateModel($bloc);
        if ($bloc->isActive() === false) {
            $bloc->setActive(true);
        }
        $bloc->save();

        $this->fileService->processBlocFiles($bloc, $model);

        $finalFileValues = $this->fileService->extractFileValues($bloc);
        $this->fileService->deleteRemovedFiles($initialFileValues, $finalFileValues);
    }

    /**
     * Repositions an element (with its subtree) relative to a target node.
     * Raises a RuntimeException on an invalid route/target or a max-level breach.
     *
     * @return array<string, mixed>|null
     */
    public function move(string $type, string $route, string $targetRoute, string $mode): ?array
    {
        $node = null;
        $modelClass = self::MODEL_CLASS[$type] ?? null;
        if ($modelClass !== null) {
            $sourceElement = Element::createFromRoute($route);
            if ($sourceElement === null || $sourceElement->getType() !== $type) {
                throw new RuntimeException('Invalid route for ' . $type . ': ' . $route);
            }
            $source = $modelClass::query()->andWhere([
                'id' => $sourceElement->getId(),
            ])->one();
            if ($source === null) {
                throw new RuntimeException('Source ' . $type . ' not found: ' . $route);
            }
            $targetElement = Element::createFromRoute($targetRoute);
            if ($targetElement === null || $targetElement->getType() !== $type) {
                throw new RuntimeException('Invalid target route for ' . $type . ': ' . $targetRoute);
            }
            $target = $modelClass::query()->andWhere([
                'id' => $targetElement->getId(),
            ])->one();
            if ($target === null) {
                throw new RuntimeException('Target ' . $type . ' not found: ' . $targetRoute);
            }
            $this->reorder($source, $target, $mode, self::MAX_LEVEL[$type]);
            $fresh = $modelClass::query()->andWhere([
                'id' => $source->getId(),
            ])->one();
            $node = $this->buildNode(Element::createFromRoute($route), $fresh);
        }

        return $node;
    }

    /**
     * Replaces the whole tag set of a content (the ContentTag pivot) with the
     * given level-2 tags, in a transaction. Mirrors dboard AbstractTags: groups
     * (level 1) cannot be assigned. Returns the updated content node.
     *
     * @param array<int, array<string, mixed>> $tags
     * @return array<string, mixed>|null
     */
    public function setContentTags(string $route, array $tags): ?array
    {
        $element = Element::createFromRoute($route);
        if ($element === null || $element->getType() !== 'content') {
            throw new RuntimeException('Invalid content route: ' . $route);
        }
        $content = Content::query()->andWhere([
            'id' => $element->getId(),
        ])->one();
        if ($content === null) {
            throw new RuntimeException('Content not found: ' . $route);
        }

        $requestedTagRoutes = [];
        foreach ($tags as $tagRef) {
            $tagRoute = $tagRef['route'] ?? null;
            $tagElement = $tagRoute !== null ? Element::createFromRoute((string) $tagRoute) : null;
            if ($tagElement === null || $tagElement->getType() !== 'tag') {
                throw new RuntimeException('Invalid tag route: ' . (string) $tagRoute);
            }
            $requestedTagRoutes[] = [
                'id' => (int) $tagElement->getId(),
                'route' => (string) $tagRoute,
            ];
        }
        $requestedTags = [];
        if (empty($requestedTagRoutes) === false) {
            $requestedTagIds = [];
            foreach ($requestedTagRoutes as $requestedTagRoute) {
                $requestedTagIds[] = $requestedTagRoute['id'];
            }
            $requestedTagsQuery = Tag::query()->andWhere([
                'in',
                'id',
                $requestedTagIds,
            ]);
            /** @var Tag $requestedTag */
            foreach ($requestedTagsQuery->each() as $requestedTag) {
                $requestedTags[$requestedTag->getId()] = $requestedTag;
            }
        }
        $tagIds = [];
        foreach ($requestedTagRoutes as $requestedTagRoute) {
            $tag = $requestedTags[$requestedTagRoute['id']] ?? null;
            if ($tag === null) {
                throw new RuntimeException('Tag not found: ' . $requestedTagRoute['route']);
            }
            if ($tag->getLevel() !== 2) {
                throw new RuntimeException('Only level-2 tags can be assigned, not groups: ' . $requestedTagRoute['route']);
            }
            $tagIds[] = $tag->getId();
        }

        $transaction = $content->db()->beginTransaction();
        try {
            (new ContentTag())->deleteAll([
                'contentId' => $content->getId(),
            ]);
            foreach ($tagIds as $tagId) {
                $pivot = new ContentTag();
                $pivot->setContentId($content->getId());
                $pivot->setTagId($tagId);
                $pivot->save();
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $fresh = Content::query()->andWhere([
            'id' => $content->getId(),
        ])->with([
            'slug',
            'blocs',
        ])->one();
        $node = $this->buildNode($element, $fresh);

        return $node;
    }

    /**
     * Replaces the whole content set of a tag (the ContentTag pivot) with the
     * given contents, in a transaction. Symmetric to setContentTags: only a
     * level-2 tag (not a group) can hold contents. Returns the updated tag node.
     *
     * @param array<int, array<string, mixed>> $contents
     * @return array<string, mixed>|null
     */
    public function setTagContents(string $route, array $contents): ?array
    {
        $element = Element::createFromRoute($route);
        if ($element === null || $element->getType() !== 'tag') {
            throw new RuntimeException('Invalid tag route: ' . $route);
        }
        $tag = Tag::query()->andWhere([
            'id' => $element->getId(),
        ])->one();
        if ($tag === null) {
            throw new RuntimeException('Tag not found: ' . $route);
        }
        if ($tag->getLevel() !== 2) {
            throw new RuntimeException('Only a level-2 tag can hold contents, not a group: ' . $route);
        }

        $requestedContentRoutes = [];
        foreach ($contents as $contentRef) {
            $contentRoute = $contentRef['route'] ?? null;
            $contentElement = $contentRoute !== null ? Element::createFromRoute((string) $contentRoute) : null;
            if ($contentElement === null || $contentElement->getType() !== 'content') {
                throw new RuntimeException('Invalid content route: ' . (string) $contentRoute);
            }
            $requestedContentRoutes[] = [
                'id' => (int) $contentElement->getId(),
                'route' => (string) $contentRoute,
            ];
        }
        $requestedContents = [];
        if (empty($requestedContentRoutes) === false) {
            $requestedContentIds = [];
            foreach ($requestedContentRoutes as $requestedContentRoute) {
                $requestedContentIds[] = $requestedContentRoute['id'];
            }
            $requestedContentsQuery = Content::query()->andWhere([
                'in',
                'id',
                $requestedContentIds,
            ]);
            /** @var Content $requestedContent */
            foreach ($requestedContentsQuery->each() as $requestedContent) {
                $requestedContents[$requestedContent->getId()] = $requestedContent;
            }
        }
        $contentIds = [];
        foreach ($requestedContentRoutes as $requestedContentRoute) {
            $content = $requestedContents[$requestedContentRoute['id']] ?? null;
            if ($content === null) {
                throw new RuntimeException('Content not found: ' . $requestedContentRoute['route']);
            }
            $contentIds[] = $content->getId();
        }

        $transaction = $tag->db()->beginTransaction();
        try {
            (new ContentTag())->deleteAll([
                'tagId' => $tag->getId(),
            ]);
            foreach ($contentIds as $contentId) {
                $pivot = new ContentTag();
                $pivot->setContentId($contentId);
                $pivot->setTagId($tag->getId());
                $pivot->save();
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $fresh = Tag::query()->andWhere([
            'id' => $tag->getId(),
        ])->with([
            'slug',
            'blocs',
        ])->one();
        $node = $this->buildNode($element, $fresh);

        return $node;
    }

    private function place(object $model, string $type, ?string $targetRoute, string $mode): void
    {
        $maxLevel = self::MAX_LEVEL[$type];
        if ($targetRoute === null) {
            $model->save();
        } else {
            $targetElement = Element::createFromRoute($targetRoute);
            if ($targetElement === null || $targetElement->getType() !== $type) {
                throw new RuntimeException('Invalid target route for ' . $type . ': ' . $targetRoute);
            }
            $modelClass = self::MODEL_CLASS[$type];
            $target = $modelClass::query()->andWhere([
                'id' => $targetElement->getId(),
            ])->one();
            if ($target === null) {
                throw new RuntimeException('Target ' . $type . ' not found: ' . $targetRoute);
            }
            $position = TreePosition::tryFrom($mode);
            if ($position === null) {
                throw new RuntimeException('Invalid mode "' . $mode . '" (allowed: into, before, after)');
            }
            $wouldBeLevel = $position === TreePosition::Into ? $target->getLevel() + 1 : $target->getLevel();
            if ($wouldBeLevel > $maxLevel) {
                throw new RuntimeException('Maximum ' . $type . ' level (' . $maxLevel . ') exceeded');
            }
            match ($position) {
                TreePosition::Before => $model->saveBefore($target),
                TreePosition::Into => $model->saveInto($target),
                TreePosition::After => $model->saveAfter($target),
            };
        }
    }

    /**
     * @param class-string $modelClass
     */
    private function treeQuery(string $modelClass, string $type, ?ContentTreeMode $mode, ?string $route): ?ActiveQueryInterface
    {
        $mode ??= $route === null ? ContentTreeMode::Roots : ContentTreeMode::Children;

        $treeQuery = null;
        if ($mode === ContentTreeMode::Roots) {
            $treeQuery = $modelClass::query()->roots();
        } elseif ($route !== null) {
            $element = Element::createFromRoute($route);
            if ($element !== null && $element->getType() === $type) {
                $node = $modelClass::query()->andWhere([
                    'id' => $element->getId(),
                ])->one();
                if ($node !== null) {
                    $treeQuery = match ($mode) {
                        ContentTreeMode::Children => $node->relativeQuery()->children(),
                        ContentTreeMode::Descendants => $node->relativeQuery()->children()->includeDescendants(),
                        ContentTreeMode::Parent => $node->relativeQuery()->parent(),
                        ContentTreeMode::Ancestors => $node->relativeQuery()->parent()->includeAncestors(),
                        ContentTreeMode::Siblings => $node->relativeQuery()->siblings(),
                        ContentTreeMode::Roots => $modelClass::query()->roots(),
                    };
                }
            }
        }

        return $treeQuery;
    }

    /**
     * @return array<string, mixed>
     */
    private function paginate(string $type, ActiveQueryInterface $elementsQuery, int $page, int $pageSize, bool $withRelevance): array
    {
        $paginator = (new ActiveQueryPaginator($elementsQuery->with([
            'slug',
        ])))
            ->withPageSize(min(max($pageSize, 1), 100))
            ->withCurrentPage(max($page, 1));

        $models = [];
        foreach ($paginator->read() as $model) {
            $models[] = $model;
        }

        return [
            'items' => $this->buildItems($type, $models, $withRelevance),
            'total' => $paginator->getTotalItems(),
            'page' => $paginator->getCurrentPage(),
            'pageSize' => $paginator->getPageSize(),
            'totalPages' => $paginator->getTotalPages(),
            'hasMore' => $paginator->isOnLastPage() === false,
        ];
    }

    /**
     * @param array<int, object> $models
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(string $type, array $models, bool $withRelevance): array
    {
        $hostNames = $this->nameMap(Host::query());
        $typeNames = $this->nameMap(Type::query());
        $languageNames = $this->nameMap(Language::query());

        $nodes = [];
        foreach ($models as $model) {
            $slug = $model->slug;
            $node = [
                'route' => Element::mapToRoute([
                    'type' => $type,
                    'id' => $model->getId(),
                ]),
                'name' => $model->getName(),
                'hazeltree' => $this->hazeltree($model),
                'language' => $this->ref($model->getLanguageId(), $languageNames),
                'type' => $this->ref($model->getTypeId(), $typeNames),
                'active' => $model->isActive(),
            ];
            if ($type === 'content') {
                $node['dateStart'] = $model->getDateStart()?->format('Y-m-d H:i:s');
                $node['dateEnd'] = $model->getDateEnd()?->format('Y-m-d H:i:s');
            }
            $node['slug'] = $slug !== null ? [
                'host' => $hostNames[$slug->getHostId()] ?? null,
                'path' => $slug->getPath(),
            ] : null;
            if ($withRelevance === true) {
                $node['relevance'] = (int) $model->relevance;
            }
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * @param array<int|string, string> $names
     * @return array<string, mixed>|null
     */
    private function ref(int|string|null $id, array $names): ?array
    {
        return $id === null ? null : [
            'id' => $id,
            'name' => $names[$id] ?? null,
        ];
    }

    /**
     * @return array<int|string, string>
     */
    private function nameMap(ActiveQueryInterface $modelsQuery): array
    {
        $names = [];
        /** @var object $model */
        foreach ($modelsQuery->each() as $model) {
            $names[$model->getId()] = $model->getName();
        }

        return $names;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNode(Element $element, object $model): array
    {
        $type = $element->getType();
        $exported = $this->exportService->export($model);
        if (is_array($exported['slug'] ?? null) === true) {
            $host = Host::query()->andWhere([
                'id' => $exported['slug']['hostId'],
            ])->one();
            $exported['slug'] = [
                'host' => $host?->getName(),
                'path' => $exported['slug']['path'] ?? null,
            ];
        }

        $translations = [];
        $translationsQuery = $model->getTranslationsQuery();
        /** @var Content|Tag $translation */
        foreach ($translationsQuery->each() as $translation) {
            $translations[$translation->getLanguageId()] = Element::mapToRoute([
                'type' => $type,
                'id' => $translation->getId(),
            ]);
        }

        $llmMenuColumn = $type === 'content' ? 'contentId' : 'tagId';
        $llmMenus = [];
        $llmMenusQuery = LlmMenu::query()->andWhere([
            $llmMenuColumn => $model->getId(),
        ]);
        /** @var LlmMenu $llmMenu */
        foreach ($llmMenusQuery->each() as $llmMenu) {
            $llmMenus[] = [
                'id' => $llmMenu->getId(),
                'name' => $llmMenu->getName(),
            ];
        }

        if (isset($exported['tags']) === true) {
            $tags = [];
            foreach ($exported['tags'] as $tag) {
                $tags[] = [
                    'route' => Element::mapToRoute([
                        'type' => 'tag',
                        'id' => $tag['id'],
                    ]),
                    'name' => $tag['name'],
                ];
            }
            $exported['tags'] = $tags;
        }

        if ($type === 'tag') {
            $contents = [];
            $contentsQuery = $model->getContentsQuery();
            /** @var Content $content */
            foreach ($contentsQuery->each() as $content) {
                $contents[] = [
                    'route' => Element::mapToRoute([
                        'type' => 'content',
                        'id' => $content->getId(),
                    ]),
                    'name' => $content->getName(),
                ];
            }
            $exported['contents'] = $contents;
        }

        $typeNames = $this->nameMap(Type::query());
        $languageNames = $this->nameMap(Language::query());
        $elasticSchemaNames = $this->nameMap(ElasticSchema::query());

        $blocModels = $model->blocs;
        foreach ($exported['blocs'] ?? [] as $index => $bloc) {
            $blocSchemaId = $bloc['elasticSchemaId'] ?? null;
            unset($bloc['elasticSchemaId']);
            $exported['blocs'][$index] = array_merge(
                [
                    'id' => $blocModels[$index]?->getId(),
                    'elasticSchema' => $this->ref($blocSchemaId, $elasticSchemaNames),
                ],
                $bloc,
            );
        }

        if (array_key_exists('elasticSchemaId', $exported) === true) {
            $nodeSchemaId = $exported['elasticSchemaId'];
            unset($exported['elasticSchemaId']);
            $exported['elasticSchema'] = $this->ref($nodeSchemaId, $elasticSchemaNames);
        }
        unset($exported['typeId'], $exported['languageId']);
        $exported['type'] = $this->ref($model->getTypeId(), $typeNames);
        $exported['language'] = $this->ref($model->getLanguageId(), $languageNames);
        $exported['hazeltree'] = $this->hazeltree($model);
        $exported['dateCreate'] = $model->getDateCreate()?->format('Y-m-d H:i:s');
        $exported['dateUpdate'] = $model->getDateUpdate()?->format('Y-m-d H:i:s');

        unset($exported['path'], $exported['id']);

        return array_merge(
            [
                'route' => $element->toRoute(),
            ],
            $exported,
            [
                'translations' => $translations,
                'llmMenus' => $llmMenus,
            ],
        );
    }
}
