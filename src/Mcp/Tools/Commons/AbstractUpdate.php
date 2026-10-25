<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Commons;

use Blackcube\Mcp\Services\ElementService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Full update of an element node in a single transaction, shared by content and
 * tag. A concrete tool sets NAME and TYPE and supplies its node-fields schema
 * (the only part that differs: content carries the publication window).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractUpdate
{
    protected const TYPE = '';

    public function __construct(
        protected ElementService $elementService,
        protected LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        $nodeFields = implode('/', array_keys(static::nodeFieldsSchema()));

        return 'Update a ' . static::TYPE . ' node by route, in one transaction. Each section is optional and '
            . 'applied only when given: content (node fields ' . $nodeFields . '), data (the node data, validated '
            . 'by its ElasticSchema), slug (host/path or redirect), sitemap (frequency/priority/active), xeo '
            . '(SEO/GEO metadata, the fields xeo.get returns, plus its own blocs), blocs (the structured blocs). '
            . 'blocs is the COMPLETE list of '
            . 'the node blocs: every existing bloc left out of it is deleted, with its files. To change one bloc, '
            . 'send all of them as ' . static::TYPE . '.get returns them and edit only that one. A bloc without id is '
            . 'created (its elasticSchemaId is required). Returns the updated node. Any validation/save failure '
            . 'rolls everything back.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        $blocItems = [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Existing bloc id (omit to create a new bloc)',
                ],
                'elasticSchemaId' => [
                    'type' => 'integer',
                    'description' => 'Bloc ElasticSchema id (required for a new bloc); resolve via elasticSchema.list',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Active (default true)',
                ],
                'data' => [
                    'type' => 'object',
                    'description' => 'Bloc dynamic fields, validated by its ElasticSchema',
                ],
            ],
        ];

        return [
            'type' => 'object',
            'properties' => [
                'route' => [
                    'type' => 'string',
                    'description' => ucfirst(static::TYPE) . ' route (dcore-XXX) to update',
                ],
                'content' => [
                    'type' => 'object',
                    'description' => 'Node fields (any subset)',
                    'properties' => static::nodeFieldsSchema(),
                ],
                'data' => [
                    'type' => 'object',
                    'description' => 'Node data, validated by the node ElasticSchema',
                ],
                'slug' => [
                    'type' => 'object',
                    'description' => 'URL of the node',
                    'properties' => [
                        'hostId' => [
                            'type' => 'integer',
                            'description' => 'Host id (resolve via host.list)',
                        ],
                        'path' => [
                            'type' => 'string',
                        ],
                        'active' => [
                            'type' => 'boolean',
                        ],
                        'targetUrl' => [
                            'type' => 'string',
                            'description' => 'Set for a redirect slug',
                        ],
                        'httpCode' => [
                            'type' => 'integer',
                            'description' => '301/302/307/308 for a redirect',
                        ],
                    ],
                ],
                'sitemap' => [
                    'type' => 'object',
                    'properties' => [
                        'frequency' => [
                            'type' => 'string',
                            'description' => 'always|hourly|daily|weekly|monthly|yearly|never',
                        ],
                        'priority' => [
                            'type' => 'number',
                            'description' => '0.0 to 1.0',
                        ],
                        'active' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
                'xeo' => [
                    'type' => 'object',
                    'description' => 'SEO/GEO metadata; may carry its own blocs',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                        'image' => [
                            'type' => 'string',
                        ],
                        'canonical' => [
                            'type' => 'boolean',
                            'description' => 'Emit a canonical link to the node slug itself',
                        ],
                        'noindex' => [
                            'type' => 'boolean',
                            'description' => 'Emit robots noindex',
                        ],
                        'nofollow' => [
                            'type' => 'boolean',
                            'description' => 'Emit robots nofollow',
                        ],
                        'og' => [
                            'type' => 'boolean',
                            'description' => 'Emit the Open Graph tags',
                        ],
                        'ogType' => [
                            'type' => 'string',
                            'description' => 'website|article|product|profile',
                        ],
                        'twitter' => [
                            'type' => 'boolean',
                            'description' => 'Emit the Twitter card tags',
                        ],
                        'twitterCard' => [
                            'type' => 'string',
                            'description' => 'summary|summary_large_image|app|player',
                        ],
                        'jsonldType' => [
                            'type' => 'string',
                            'description' => 'JSON-LD page type, default WebPage (WebPage|AboutPage|ContactPage|CollectionPage|FAQPage|ItemPage|SearchResultsPage|ProfilePage|CheckoutPage|Article|BlogPosting|NewsArticle|TechArticle|ScholarlyArticle|Report)',
                        ],
                        'speakable' => [
                            'type' => 'boolean',
                            'description' => 'Mark the page speakable in the JSON-LD',
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'description' => 'Comma-separated keywords',
                        ],
                        'accessibleForFree' => [
                            'type' => 'boolean',
                            'description' => 'JSON-LD accessibleForFree (default true)',
                        ],
                        'active' => [
                            'type' => 'boolean',
                            'description' => 'Whether the xeo metadata is emitted',
                        ],
                        'blocs' => [
                            'type' => 'array',
                            'items' => $blocItems,
                            'description' => 'Complete list of the xeo blocs: omitted blocs are deleted with their files',
                        ],
                    ],
                ],
                'blocs' => [
                    'type' => 'array',
                    'items' => $blocItems,
                    'description' => 'The complete list of the ' . static::TYPE . ' blocs: omitted blocs are deleted with their files. Send every bloc from ' . static::TYPE . '.get and edit only the ones to change; a bloc without id is created',
                ],
            ],
            'required' => [
                'route',
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $content
     * @param array<string, mixed>|null $data
     * @param array<string, mixed>|null $slug
     * @param array<string, mixed>|null $sitemap
     * @param array<string, mixed>|null $xeo
     * @param array<int, array<string, mixed>>|null $blocs
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $route, ?array $content = null, ?array $data = null, ?array $slug = null, ?array $sitemap = null, ?array $xeo = null, ?array $blocs = null): array|CallToolResult
    {
        $toolResult = null;
        $payload = [];
        if ($content !== null) {
            $payload['content'] = $content;
        }
        if ($data !== null) {
            $payload['data'] = $data;
        }
        if ($slug !== null) {
            $payload['slug'] = $slug;
        }
        if ($sitemap !== null) {
            $payload['sitemap'] = $sitemap;
        }
        if ($xeo !== null) {
            $payload['xeo'] = $xeo;
        }
        if ($blocs !== null) {
            $payload['blocs'] = $blocs;
        }
        try {
            $toolResult = $this->elementService->update(static::TYPE, $route, $payload);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
            ]);
            $toolResult = CallToolResult::error([
                new TextContent($e->getMessage()),
            ]);
        }

        return $toolResult;
    }

    /**
     * Node-fields section properties, the only schema part that differs between
     * content and tag.
     *
     * @return array<string, mixed>
     */
    abstract protected static function nodeFieldsSchema(): array;
}
