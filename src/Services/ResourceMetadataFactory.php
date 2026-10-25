<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dboard\DboardConfig;
use Blackcube\Mcp\Dboard\McpAddon;
use Mcp\Server\Transport\Http\OAuth\ProtectedResourceMetadata;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Builds the protected resource metadata of the MCP server (RFC 9728) from
 * the request URL: the resource is the MCP endpoint, its authorization server
 * is the dboard population of the addon, and its scopes are the permissions
 * the addon publishes. The document is served under
 * /.well-known/oauth-protected-resource followed by the endpoint path.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ResourceMetadataFactory
{
    public const METADATA_PATH = '/.well-known/oauth-protected-resource';
    private const OAUTH_PATH = '/oauth/';

    public function __construct(
        private readonly DboardConfig $dboardConfig,
        private readonly string $endpoint,
    ) {
    }

    public function fromRequest(ServerRequestInterface $request): ProtectedResourceMetadata
    {
        $uri = $request->getUri();
        $baseUrl = $uri->getScheme() . '://' . $uri->getAuthority();
        return new ProtectedResourceMetadata(
            resource: $baseUrl . $this->endpoint,
            authorizationServers: [
                $baseUrl . $this->dboardConfig->routePrefix . self::OAUTH_PATH . McpAddon::getPopulation(),
            ],
            scopesSupported: McpAddon::getScopes(),
        );
    }
}
