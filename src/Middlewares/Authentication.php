<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Middlewares;

use Blackcube\Mcp\Services\ResourceMetadataFactory;
use Mcp\Server\Transport\Http\Middleware\AuthorizationMiddleware;
use Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Authenticates the Bearer credential, key or dboard access token, and
 * attaches the granted permissions to the request before the MCP handler runs.
 * A refused request carries the resource metadata hint an OAuth2 client needs
 * to find the dboard authorization server.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Authentication implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthorizationTokenValidatorInterface $tokenValidator,
        private readonly ResourceMetadataFactory $resourceMetadataFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $middleware = new AuthorizationMiddleware($this->tokenValidator, $this->resourceMetadataFactory->fromRequest($request));
        return $middleware->process($request, $requestHandler);
    }
}
