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
use Blackcube\Mcp\Models\Forms\ParameterForm;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * parameter.create - Create a global parameter (domain + name unique).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'parameter.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_PARAMETER_CREATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create a global parameter. The domain + name pair must be unique; fails if it already exists (use parameter.update to change a value).';
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
                    'description' => 'Parameter domain, e.g. PROJECT',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Parameter name, e.g. NAME',
                ],
                'value' => [
                    'type' => 'string',
                    'description' => 'Parameter value',
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
    public function handle(string $domain, string $name, ?string $value = null): array|CallToolResult
    {
        $toolResult = null;
        $form = new ParameterForm();
        $form->setScenario('create');
        $form->load([
            'domain' => $domain,
            'name' => $name,
            'value' => $value,
        ], '');
        if ($form->validate() === false) {
            $toolResult = CallToolResult::error([
                new TextContent(implode('; ', $form->getValidationResult()->getErrorMessages())),
            ]);
        } else {
            $parameter = new Parameter();
            $form->populateModel($parameter);
            try {
                $parameter->save();
                $toolResult = [
                    'domain' => $parameter->getDomain(),
                    'name' => $parameter->getName(),
                    'value' => $parameter->getValue(),
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

        return $toolResult;
    }
}
