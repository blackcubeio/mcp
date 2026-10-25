<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Md;

use Blackcube\Dcore\Helpers\Element;
use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\Tag;
use Blackcube\Dcore\Services\ElasticMdService;
use Blackcube\Dboard\Components\Rbac;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * md.import - Write a markdown document back into an element.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Import
{
    public const NAME = 'md.import';
    public const PERMISSIONS = [
        Rbac::PERMISSION_IA_IMPORT,
    ];

    public function __construct(
        private readonly ElasticMdService $elasticMdService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Import a markdown document (the md.export format: front matter + typed blocks) into a content or tag: the page data and the blocs are replaced by the parsed document, in one transaction. Unknown blocks are reported as warnings. Returns success, errors, warnings and blocsCreated.';
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
                    'description' => 'Content or tag route (dcore-XXX)',
                ],
                'markdown' => [
                    'type' => 'string',
                    'description' => 'The markdown document to import (md.export format)',
                ],
            ],
            'required' => [
                'route',
                'markdown',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $route, string $markdown): array|CallToolResult
    {
        $toolResult = null;
        $element = Element::createFromRoute($route);
        if ($element === null) {
            $toolResult = CallToolResult::error([
                new TextContent('Invalid route: ' . $route),
            ]);
        } else {
            $model = $element->getType() === 'content'
                ? Content::query()->andWhere([
                    'id' => $element->getId(),
                ])->one()
                : Tag::query()->andWhere([
                    'id' => $element->getId(),
                ])->one();
            if ($model === null) {
                $toolResult = CallToolResult::error([
                    new TextContent('Element not found: ' . $route),
                ]);
            } else {
                try {
                    $toolResult = array_merge(
                        [
                            'route' => $element->toRoute(),
                        ],
                        $this->elasticMdService->import($model, $markdown),
                    );
                } catch (Throwable $e) {
                    $this->logger->error($e->getMessage(), [
                        'exception' => $e,
                    ]);
                    $toolResult = CallToolResult::error([
                        new TextContent($e->getMessage()),
                    ]);
                }
            }
        }

        return $toolResult;
    }
}
