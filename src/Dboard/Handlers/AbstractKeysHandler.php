<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Dboard\Handlers;

use Blackcube\Dboard\Components\Rbac;
use Blackcube\Dboard\DboardConfig;
use Blackcube\Dboard\Handlers\Commons\AbstractBaseHandler;
use Blackcube\Mcp\Dboard\McpAddon;
use Blackcube\Mcp\Services\ApiKeyService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Base for the MCP key admin handlers: injects ApiKeyService and renders the
 * addon's own views (@mcp/Dboard/Views) while keeping the dboard layout.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractKeysHandler extends AbstractBaseHandler
{
    public function __construct(
        LoggerInterface $logger,
        DboardConfig $dboardConfig,
        WebViewRenderer $viewRenderer,
        ResponseFactoryInterface $responseFactory,
        JsonResponseFactory $jsonResponseFactory,
        UrlGeneratorInterface $urlGenerator,
        Aliases $aliases,
        TranslatorInterface $translator,
        protected ApiKeyService $apiKeyService,
        protected CurrentRoute $currentRoute,
    ) {
        parent::__construct(
            logger: $logger,
            dboardConfig: $dboardConfig,
            viewRenderer: $viewRenderer,
            responseFactory: $responseFactory,
            jsonResponseFactory: $jsonResponseFactory,
            urlGenerator: $urlGenerator,
            aliases: $aliases,
            translator: $translator,
        );
    }

    public function getViewsAlias(): string
    {
        return '@mcp/Dboard/Views';
    }

    /**
     * The permissions the addon publishes, grouped by role and labelled by
     * their action: what the key screens offer, laid out as the permissions
     * drawer of dboard.
     *
     * @return array<string, array<string, string>>
     */
    protected function scopeGroups(): array
    {
        $groups = [];
        foreach (McpAddon::getScopes() as $scope) {
            $groups[Rbac::extractRole($scope)][$scope] = Rbac::extractPermission($scope);
        }
        return $groups;
    }
}
