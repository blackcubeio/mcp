<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Commons;

use Blackcube\Dcore\Helpers\Element;
use Blackcube\Mcp\Services\ElementService;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;

/**
 * Gets an element node by its route, shared by content and tag. A concrete tool
 * sets NAME and TYPE and lists the relations its node serializes.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractGet
{
    protected const TYPE = '';

    public function __construct(
        protected ElementService $elementService,
    ) {}

    public static function description(): string
    {
        return 'Get a ' . static::TYPE . ' node by its route (dcore-XXX): full serialized data ('
            . implode(', ', static::serializedRelations()) . '), linked translations and llmMenus. '
            . 'SEO metadata (xeo, sitemap) is served separately by xeo.get.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'route' => [
                    'type' => 'string',
                    'description' => ucfirst(static::TYPE) . ' route (dcore-XXX)',
                ],
            ],
            'required' => [
                'route',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $route): array|CallToolResult
    {
        $toolResult = null;
        $element = Element::createFromRoute($route);
        if ($element === null || $element->getType() !== static::TYPE) {
            $toolResult = CallToolResult::error([
                new TextContent('Invalid ' . static::TYPE . ' route: ' . $route),
            ]);
        } else {
            $node = $this->elementService->get($element);
            if ($node === null) {
                $toolResult = CallToolResult::error([
                    new TextContent(ucfirst(static::TYPE) . ' not found: ' . $route),
                ]);
            } else {
                $toolResult = $node;
            }
        }

        return $toolResult;
    }

    /**
     * Relations serialized with the node, the only part that differs between
     * content and tag (a tag is not taggable).
     *
     * @return string[]
     */
    abstract protected static function serializedRelations(): array;
}
