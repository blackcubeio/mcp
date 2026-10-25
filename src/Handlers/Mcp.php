<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Handlers;

use Blackcube\Mcp\Mcp\Server\McpServerFactory;
use Blackcube\Mcp\ServerInfo;
use Blackcube\Mcp\Services\ScopeGuard;
use Mcp\Server\Authorization\AccessToken;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Runs the MCP Streamable HTTP transport once authentication has passed.
 *
 * The granted scopes (carried by the access token the authorization step
 * attaches) drive which tools the server exposes.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Mcp implements RequestHandlerInterface
{
    /**
     * @param string[] $allowedHosts Host names allowed to reach the endpoint; empty means the host of the current request.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ScopeGuard $scopeGuard,
        private readonly ContainerInterface $container,
        private readonly array $allowedHosts,
        private readonly ServerInfo $serverInfo,
        private readonly ?CacheInterface $cache = null,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $scopes = [];
        $accessToken = $request->getAttribute(AccessToken::class);
        if ($accessToken instanceof AccessToken) {
            $scopes = $accessToken->getScopes();
        }

        $allowedHosts = $this->allowedHosts;
        if (empty($allowedHosts) === true) {
            $allowedHosts = [
                $request->getUri()->getHost(),
            ];
        }

        // Same stack as StreamableHttpTransport::defaultMiddleware(), with the allowed hosts;
        // the transport applies ProtocolVersionMiddleware to handshake-era requests itself.
        $middlewares = [
            new CorsMiddleware(),
            new DnsRebindingProtectionMiddleware(allowedHosts: $allowedHosts),
        ];
        $transport = new StreamableHttpTransport(
            request: $request,
            logger: $this->logger,
            middleware: $middlewares,
        );

        /** @var ResponseInterface $httpResponse */
        $httpResponse = McpServerFactory::create($this->cache, $scopes, $this->scopeGuard, $this->container, $this->serverInfo)->run($transport);

        return $httpResponse;
    }
}
