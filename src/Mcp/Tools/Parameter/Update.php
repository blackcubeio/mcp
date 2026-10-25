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
 * parameter.update - Update an existing parameter value (by domain + name).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'parameter.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_PARAMETER_UPDATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update the value of an existing parameter, identified by its domain + name. Fails if it does not exist (use parameter.create first).';
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
                'value' => [
                    'type' => 'string',
                    'description' => 'New parameter value',
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
        $parameter = Parameter::query()->andWhere([
            'domain' => $domain,
            'name' => $name,
        ])->one();
        if ($parameter === null) {
            $toolResult = CallToolResult::error([
                new TextContent('Parameter not found: ' . $domain . '.' . $name),
            ]);
        } else {
            $form = new ParameterForm();
            $form->setScenario('edit');
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
        }

        return $toolResult;
    }
}
