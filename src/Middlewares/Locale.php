<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Sets the translator locale of the MCP endpoint, so every message sent back
 * to the LLM (validation errors included) is in the MCP locale (English by
 * default) whatever the host application locale.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Locale implements MiddlewareInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $locale,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $this->translator->setLocale($this->locale);

        return $requestHandler->handle($request);
    }
}
