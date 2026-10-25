<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Author;

use Blackcube\Dcore\Models\Author;
use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Models\Forms\AuthorForm;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * author.create - Create an author.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'author.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_AUTHOR_CREATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create an author: firstname and lastname are required, the other fields (email, jobTitle, worksFor, knowsAbout, sameAs, url, image) are optional schema.org profile data. Returns the created author, including its generated id.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'firstname' => [
                    'type' => 'string',
                    'description' => 'First name',
                ],
                'lastname' => [
                    'type' => 'string',
                    'description' => 'Last name',
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'Contact email (optional)',
                ],
                'jobTitle' => [
                    'type' => 'string',
                    'description' => 'Professional title, e.g. CTO (optional)',
                ],
                'worksFor' => [
                    'type' => 'string',
                    'description' => 'Organizations, one per line (optional)',
                ],
                'knowsAbout' => [
                    'type' => 'string',
                    'description' => 'Areas of expertise, one per line (optional)',
                ],
                'sameAs' => [
                    'type' => 'string',
                    'description' => 'Social/profile URLs, one per line (optional)',
                ],
                'url' => [
                    'type' => 'string',
                    'description' => 'Author profile page URL (optional)',
                ],
                'image' => [
                    'type' => 'string',
                    'description' => 'Author photo URL (optional)',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Active (default true)',
                ],
            ],
            'required' => [
                'firstname',
                'lastname',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $firstname, string $lastname, ?string $email = null, ?string $jobTitle = null, ?string $worksFor = null, ?string $knowsAbout = null, ?string $sameAs = null, ?string $url = null, ?string $image = null, bool $active = true): array|CallToolResult
    {
        $toolResult = null;
        $provided = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'active' => $active,
        ];
        if ($email !== null) {
            $provided['email'] = $email;
        }
        if ($jobTitle !== null) {
            $provided['jobTitle'] = $jobTitle;
        }
        if ($worksFor !== null) {
            $provided['worksFor'] = $worksFor;
        }
        if ($knowsAbout !== null) {
            $provided['knowsAbout'] = $knowsAbout;
        }
        if ($sameAs !== null) {
            $provided['sameAs'] = $sameAs;
        }
        if ($url !== null) {
            $provided['url'] = $url;
        }
        if ($image !== null) {
            $provided['image'] = $image;
        }
        $form = new AuthorForm();
        $form->setScenario('create');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            $toolResult = CallToolResult::error([
                new TextContent(implode('; ', $form->getValidationResult()->getErrorMessages())),
            ]);
        } else {
            $author = new Author();
            $form->populateModel($author);
            try {
                $author->save();
                $toolResult = Index::item($author);
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
