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
 * author.update - Update an existing author (by id).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'author.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_AUTHOR_UPDATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update an existing author, identified by its id. Only the provided fields change. Fails if it does not exist. Returns the updated author.';
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
                    'type' => 'integer',
                    'description' => 'Author id to update',
                ],
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
                    'description' => 'Contact email',
                ],
                'jobTitle' => [
                    'type' => 'string',
                    'description' => 'Professional title',
                ],
                'worksFor' => [
                    'type' => 'string',
                    'description' => 'Organizations, one per line',
                ],
                'knowsAbout' => [
                    'type' => 'string',
                    'description' => 'Areas of expertise, one per line',
                ],
                'sameAs' => [
                    'type' => 'string',
                    'description' => 'Social/profile URLs, one per line',
                ],
                'url' => [
                    'type' => 'string',
                    'description' => 'Author profile page URL',
                ],
                'image' => [
                    'type' => 'string',
                    'description' => 'Author photo URL',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Active',
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
    public function handle(int $id, ?string $firstname = null, ?string $lastname = null, ?string $email = null, ?string $jobTitle = null, ?string $worksFor = null, ?string $knowsAbout = null, ?string $sameAs = null, ?string $url = null, ?string $image = null, ?bool $active = null): array|CallToolResult
    {
        $toolResult = null;
        $author = Author::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($author === null) {
            $toolResult = CallToolResult::error([
                new TextContent('Author not found: ' . $id),
            ]);
        } else {
            $provided = [];
            if ($firstname !== null) {
                $provided['firstname'] = $firstname;
            }
            if ($lastname !== null) {
                $provided['lastname'] = $lastname;
            }
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
            if ($active !== null) {
                $provided['active'] = $active;
            }
            $form = new AuthorForm();
            $form->setScenario('edit');
            $form->setPatchFields(array_keys($provided));
            $form->load($provided, '');
            if ($form->validate() === false) {
                $toolResult = CallToolResult::error([
                    new TextContent(implode('; ', $form->getValidationResult()->getErrorMessages())),
                ]);
            } else {
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
        }

        return $toolResult;
    }
}
