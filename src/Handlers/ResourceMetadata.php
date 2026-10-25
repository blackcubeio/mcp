<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Handlers;

use Blackcube\Mcp\Services\ResourceMetadataFactory;
use Mcp\Server\Transport\Http\OAuth\ProtectedResourceMetadataHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Serves the protected resource metadata document (RFC 9728) an MCP client
 * reads to find the authorization server of this resource.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ResourceMetadata implements RequestHandlerInterface
{
    public function __construct(
        private readonly ResourceMetadataFactory $resourceMetadataFactory,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $metadataHandler = new ProtectedResourceMetadataHandler(
            $this->resourceMetadataFactory->fromRequest($request),
            $this->responseFactory,
            $this->streamFactory,
        );
        return $metadataHandler->handle($request);
    }
}
