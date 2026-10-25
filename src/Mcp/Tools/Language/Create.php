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
 * language.create - Create a site language (id = ISO code, unique).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'language.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_LANGUAGE_CREATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create a site language; its id is the ISO code (e.g. "fr"). Fails if it already exists (use language.update to change it). Returns the created language.';
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
                    'description' => 'Language ISO code, e.g. fr',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Display name, e.g. Français',
                ],
                'main' => [
                    'type' => 'boolean',
                    'description' => 'Default language (default false)',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Available (default true)',
                ],
            ],
            'required' => [
                'id',
                'name',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $id, string $name, bool $main = false, bool $active = true): array|CallToolResult
    {
        $toolResult = null;
        $form = new LanguageForm();
        $form->setScenario('create');
        $form->load([
            'id' => $id,
            'name' => $name,
            'main' => $main,
            'active' => $active,
        ], '');
        if ($form->validate() === false) {
            $toolResult = CallToolResult::error([
                new TextContent(implode('; ', $form->getValidationResult()->getErrorMessages())),
            ]);
        } else {
            $language = new Language();
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

        return $toolResult;
    }
}
