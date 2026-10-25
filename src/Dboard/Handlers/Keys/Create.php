<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Dboard\Handlers\Keys;

use Blackcube\Mcp\Dboard\Forms\KeyForm;
use Blackcube\Mcp\Dboard\Handlers\AbstractKeysHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;

/**
 * Creates an MCP key: a name, the granted scopes and an optional TTL. The
 * plaintext token is shown once on success.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Create extends AbstractKeysHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        $keyForm = new KeyForm(translator: $this->translator);
        $keyForm->setScenario('create');
        $created = null;
        $errors = [];
        if ($request->getMethod() === Method::POST) {
            $keyForm->load($this->getBodyParams() ?? []);
            if ($keyForm->validate() === true) {
                $created = $this->apiKeyService->create(
                    $keyForm->getName(),
                    $keyForm->getScopes(),
                    $keyForm->getTtlDays(),
                );
            } else {
                $errors = $keyForm->getValidationResult()->getErrorMessages();
            }
        }

        if ($created !== null) {
            $createResponse = $this->render('Keys/created', [
                'token' => $created['token'],
                'urlGenerator' => $this->urlGenerator,
            ]);
        } else {
            $createResponse = $this->render('Keys/create', [
                'keyForm' => $keyForm,
                'groups' => $this->scopeGroups(),
                'errors' => $errors,
                'urlGenerator' => $this->urlGenerator,
            ]);
        }

        return $createResponse;
    }
}
