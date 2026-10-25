<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Parameter;

use Blackcube\Dcore\Models\Parameter;
use Blackcube\Dboard\Components\Rbac;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * parameter.delete - Delete a parameter (by domain + name).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Delete
{
    public const NAME = 'parameter.delete';
    public const PERMISSIONS = [
        Rbac::PERMISSION_PARAMETER_DELETE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Delete a parameter, identified by its domain + name. Fails if it does not exist. Returns the deleted parameter.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'domain' => [
                    'type' => 'string',
                    'description' => 'Parameter domain',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Parameter name',
                ],
            ],
            'required' => [
                'domain',
                'name',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $domain, string $name): array|CallToolResult
    {
        $toolResult = null;
        $parameter = Parameter::query()->andWhere([
            'domain' => $domain,
            'name' => $name,
        ])->one();
        if ($parameter === null) {
            $toolResult = CallToolResult::error([
                new TextContent('Parameter not found: ' . $domain . '.' . $name),
            ]);
        } else {
            $deleted = [
                'domain' => $parameter->getDomain(),
                'name' => $parameter->getName(),
                'value' => $parameter->getValue(),
            ];
            try {
                $parameter->delete();
                $toolResult = $deleted;
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [
                    'exception' => $e,
                ]);
                $toolResult = CallToolResult::error([
                    new TextContent($e->getMessage()),
                ]);
            }
        }

        return $toolResult;
    }
}
