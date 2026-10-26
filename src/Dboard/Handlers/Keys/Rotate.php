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
use Blackcube\Handler\OutputType;
use Blackcube\Dboard\Handlers\Commons\AbstractAjaxHandler;
use Blackcube\Mcp\Models\ApiKey;
use Blackcube\Mcp\Services\ApiKeyService;
use Blackcube\Bleet\Bleet;
use Blackcube\Bleet\Enums\DialogAction;
use Blackcube\Bleet\Enums\DialogType;
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
 * Rotate action (POST) for an MCP key: issues a fresh token and resets its
 * expiration. The new token is shown once in the dialog; the list reloads.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Rotate extends AbstractAjaxHandler
{
    private ?string $newToken = null;

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
        $rotated = $this->apiKeyService->rotate($this->models['main']->getId());
        $this->newToken = $rotated['token'] ?? null;
    }

    protected function prepareOutputData(): array
    {
        $content = (string) $this->viewRenderer
            ->withViewPath($this->aliases->get('@mcp/Dboard/Views'))
            ->withLayout(null)
            ->render('Keys/_token', [
                'token' => $this->newToken,
                'translator' => $this->translator,
            ])
            ->getBody();

        return [
            'type' => OutputType::Json->value,
            'data' => [
                ...AureliaCommunication::dialog(DialogAction::Keep),
                ...AureliaCommunication::dialogContent(Bleet::dialogFrame(DialogType::Modal)
                    ->title($this->translator->translate('New token', category: 'mcp'))
                    ->success()
                    ->content($content)
                    ->render()),
                ...AureliaCommunication::ajaxify(
                    'mcp-keys-list',
                    $this->urlGenerator->generate('mcp.keys'),
                ),
                ...AureliaCommunication::toast(
                    $this->translator->translate('Success', category: 'dboard-common'),
                    $this->translator->translate('Key "{name}" rotated.', [
                        'name' => $this->models['main']->getName(),
                    ], 'mcp'),
                    UiLevel::Success,
                ),
            ],
        ];
    }
}
