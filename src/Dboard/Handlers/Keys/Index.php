<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Dboard\Handlers\Keys;

use Blackcube\Mcp\Dboard\Handlers\AbstractKeysHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Lists the MCP keys (active and expired), with their scopes and lifecycle.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Index extends AbstractKeysHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        $keys = $this->apiKeyService->getKeys(includeExpired: true);
        $expiredIds = $this->apiKeyService->getExpiredIds($keys);

        if ($this->isAjaxify() === true) {
            $indexResponse = $this->renderPartial('Keys/_list', [
                'keys' => $keys,
                'expiredIds' => $expiredIds,
                'urlGenerator' => $this->urlGenerator,
                'translator' => $this->translator,
            ]);
        } else {
            $indexResponse = $this->render('Keys/index', [
                'keys' => $keys,
                'expiredIds' => $expiredIds,
                'urlGenerator' => $this->urlGenerator,
            ]);
        }

        return $indexResponse;
    }
}
