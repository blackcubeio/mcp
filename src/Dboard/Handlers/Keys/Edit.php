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
 * Edits an MCP key: its name, scopes and expiration date, without touching the
 * token.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Edit extends AbstractKeysHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        $id = (int) $this->currentRoute->getArgument('id');
        $apiKey = $this->apiKeyService->getKey($id);

        if ($apiKey === null) {
            $editResponse = $this->redirect('mcp.keys');
        } else {
            $keyForm = new KeyForm(translator: $this->translator);
            $keyForm->setScenario('edit');
            $errors = [];
            $saved = false;

            if ($request->getMethod() === Method::POST) {
                $keyForm->load($this->getBodyParams() ?? []);
                if ($keyForm->validate() === true) {
                    $this->apiKeyService->update(
                        $id,
                        $keyForm->getName(),
                        $keyForm->getScopes(),
                        $keyForm->getDateExpire(),
                    );
                    $saved = true;
                } else {
                    $errors = $keyForm->getValidationResult()->getErrorMessages();
                }
            } else {
                $keyForm->initFromModel($apiKey);
            }

            if ($saved === true) {
                $editResponse = $this->redirect('mcp.keys');
            } else {
                $editResponse = $this->render('Keys/edit', [
                    'keyForm' => $keyForm,
                    'groups' => $this->scopeGroups(),
                    'errors' => $errors,
                    'apiKey' => $apiKey,
                    'urlGenerator' => $this->urlGenerator,
                ]);
            }
        }

        return $editResponse;
    }
}
