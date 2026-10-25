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
 * host.create - Create a host (served domain).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create
{
    public const NAME = 'host.create';
    public const PERMISSIONS = [
        Rbac::PERMISSION_HOST_CREATE,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function description(): string
    {
        return 'Create a host (a served domain): name is the domain, siteName/siteAlternateName/siteDescription feed the site-wide metadata. Returns the created host.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Domain name (e.g. www.blackcube.io)',
                ],
                'siteName' => [
                    'type' => 'string',
                    'description' => 'Site name',
                ],
                'siteAlternateName' => [
                    'type' => 'string',
                    'description' => 'Site alternate name',
                ],
                'siteDescription' => [
                    'type' => 'string',
                    'description' => 'Site description',
                ],
                'active' => [
                    'type' => 'boolean',
                    'description' => 'Whether the host is served (default true)',
                ],
            ],
            'required' => [
                'name',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|CallToolResult
     */
    public function handle(string $name, ?string $siteName = null, ?string $siteAlternateName = null, ?string $siteDescription = null, bool $active = true): array|CallToolResult
    {
        $toolResult = null;
        $provided = [
            'name' => $name,
            'active' => $active,
        ];
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
        $form->setScenario('create');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            $toolResult = CallToolResult::error([
                new TextContent(implode('; ', $form->getValidationResult()->getErrorMessages())),
            ]);
        } else {
            $host = new Host();
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

        return $toolResult;
    }
}
