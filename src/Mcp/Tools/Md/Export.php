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
 * md.export - Export an element as authorable markdown.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Export
{
    public const NAME = 'md.export';
    public const PERMISSIONS = [
        Rbac::PERMISSION_IA_EXPORT,
    ];

    public function __construct(
        private readonly ElasticMdService $elasticMdService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Export a content or tag as authorable markdown: front matter, the dictionaries of the allowed blocs (driven by the mdMapping of the schemas) and the existing content. Edit that document and write it back with md.import.';
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
                'prompt' => [
                    'type' => 'string',
                    'description' => 'Optional editorial brief embedded in the front matter',
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
    public function handle(string $route, string $prompt = ''): array|CallToolResult
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
                    $toolResult = [
                        'route' => $element->toRoute(),
                        'markdown' => $this->elasticMdService->export($model, $prompt),
                    ];
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
