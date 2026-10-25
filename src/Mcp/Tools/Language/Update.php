<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Language;

use Blackcube\Dcore\Models\Language;
use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Models\Forms\LanguageForm;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * language.update - Update an existing language (by its id/ISO code).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'language.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_LANGUAGE_UPDATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update an existing language, identified by its id (ISO code). Only the provided fields change. Fails if it does not exist. Returns the updated language.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'Language ISO code to update',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New display name',
                ],
                'main' => [
                    'type' => 'boolean',
                    'description' => 'Default language',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Available',
                ],
            ],
            'required' => [
                'id',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $id, ?string $name = null, ?bool $main = null, ?bool $active = null): array|CallToolResult
    {
        $toolResult = null;
        $language = Language::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($language === null) {
            $toolResult = CallToolResult::error([
                new TextContent('Language not found: ' . $id),
            ]);
        } else {
            $provided = [];
            if ($name !== null) {
                $provided['name'] = $name;
            }
            if ($main !== null) {
                $provided['main'] = $main;
            }
            if ($active !== null) {
                $provided['active'] = $active;
            }
            $form = new LanguageForm();
            $form->setScenario('edit');
            $form->setPatchFields(array_keys($provided));
            $form->load($provided, '');
            if ($form->validate() === false) {
                $toolResult = CallToolResult::error([
                    new TextContent(implode('; ', $form->getValidationResult()->getErrorMessages())),
                ]);
            } else {
                $form->populateModel($language);
                try {
                    $language->save();
                    $toolResult = Index::item($language);
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
