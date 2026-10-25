<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools\Host;

use Blackcube\Dcore\Models\Host;
use Blackcube\Dboard\Components\Rbac;
use Blackcube\Mcp\Models\Forms\HostForm;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * host.update - Update an existing host (by id). No delete (active=false to disable).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Update
{
    public const NAME = 'host.update';
    public const PERMISSIONS = [
        Rbac::PERMISSION_HOST_UPDATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Update an existing host (by id): its name, active flag and site metadata. Only the provided fields change. Hosts are created with host.create and never deleted — set active=false to disable one. Returns the updated host.';
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
                    'description' => 'Host id to update',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Host name (domain)',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Whether the host is served',
                ],
                'siteName' => [
                    'type' => 'string',
                    'description' => 'Site name (schema.org)',
                ],
                'siteAlternateName' => [
                    'type' => 'string',
                    'description' => 'Site alternate name',
                ],
                'siteDescription' => [
                    'type' => 'string',
                    'description' => 'Site description',
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
    public function handle(int $id, ?string $name = null, ?bool $active = null, ?string $siteName = null, ?string $siteAlternateName = null, ?string $siteDescription = null): array|CallToolResult
    {
        $toolResult = null;
        $host = Host::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($host === null) {
            $toolResult = CallToolResult::error([
                new TextContent('Host not found: ' . $id),
            ]);
        } else {
            $provided = [];
            if ($name !== null) {
                $provided['name'] = $name;
            }
            if ($active !== null) {
                $provided['active'] = $active;
            }
            if ($siteName !== null) {
                $provided['siteName'] = $siteName;
            }
            if ($siteAlternateName !== null) {
                $provided['siteAlternateName'] = $siteAlternateName;
            }
            if ($siteDescription !== null) {
                $provided['siteDescription'] = $siteDescription;
            }
            $form = new HostForm();
            $form->setScenario('edit');
            $form->setPatchFields(array_keys($provided));
            $form->load($provided, '');
            if ($form->validate() === false) {
                $toolResult = CallToolResult::error([
                    new TextContent(implode('; ', $form->getValidationResult()->getErrorMessages())),
                ]);
            } else {
                $form->populateModel($host);
                try {
                    $host->save();
                    $toolResult = Index::item($host);
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
