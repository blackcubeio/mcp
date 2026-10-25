<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Dboard\Handlers\Keys;

use Blackcube\Dboard\Components\ActionModel;
use Blackcube\Dboard\DboardConfig;
use Blackcube\Dboard\Enums\OutputType;
use Blackcube\Dboard\Handlers\Commons\AbstractAjaxHandler;
use Blackcube\Mcp\Models\ApiKey;
use Blackcube\Mcp\Services\ApiKeyService;
use Blackcube\Bleet\Enums\DialogAction;
use Blackcube\Bleet\Enums\UiLevel;
use Blackcube\Bleet\Helper\AureliaCommunication;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Revoke action (DELETE) for an MCP key: soft-revokes it (the token stops being
 * accepted, the record stays). Closes the dialog and reloads the list.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Delete extends AbstractAjaxHandler
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
        CurrentRoute $currentRoute,
        protected ApiKeyService $apiKeyService,
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
            currentRoute: $currentRoute,
        );
    }

    protected function getActionModels(): array
    {
        return [
            'main' => new ActionModel(modelClass: ApiKey::class, formModelClass: null, isMain: true),
        ];
    }

    protected function handleMethod(): void
    {
        $this->apiKeyService->revoke($this->models['main']->getId());
    }

    protected function prepareOutputData(): array
    {
        return [
            'type' => OutputType::Json->value,
            'data' => [
                ...AureliaCommunication::dialog(DialogAction::Close),
                ...AureliaCommunication::ajaxify(
                    'mcp-keys-list',
                    $this->urlGenerator->generate('mcp.keys'),
                ),
                ...AureliaCommunication::toast(
                    $this->translator->translate('Success', category: 'dboard-common'),
                    $this->translator->translate('Key "{name}" revoked.', [
                        'name' => $this->models['main']->getName(),
                    ], 'mcp'),
                    UiLevel::Success,
                ),
            ],
        ];
    }
}
