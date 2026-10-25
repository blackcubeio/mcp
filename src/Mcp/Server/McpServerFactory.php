<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Server;

use Blackcube\Mcp\ServerInfo;
use Blackcube\Mcp\Services\ScopeGuard;
use Blackcube\Mcp\Mcp\Tools;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\File\FileCache;

/**
 * Builds the MCP server. Only the tools granted to the current key are
 * registered, so tools/list naturally exposes the authorized surface.
 *
 * Sessions persist across requests via the host cache when it is shared; an
 * in-memory cache (ArrayCache) is swapped for a filesystem cache under the
 * runtime alias, so persistence holds with or without a shared application
 * cache.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class McpServerFactory
{
    /**
     * @param string[] $scopes
     */
    public static function create(?CacheInterface $cache, array $scopes, ScopeGuard $scopeGuard, ContainerInterface $container, ServerInfo $serverInfo): Server
    {
        $builder = Server::builder()->setServerInfo($serverInfo->name, $serverInfo->version)->setContainer($container);
        $builder->setInstructions(self::instructions());
        if ($cache === null || $cache instanceof ArrayCache) {
            $cache = new FileCache($container->get(Aliases::class)->get('@runtime') . '/mcp/cache');
        }
        $builder->setSession(new CacheSessionStore($cache));

        $surface = [];
        foreach (Tools\Registry::TOOLS as $tool) {
            if ($scopeGuard->isGranted($scopes, $tool::PERMISSIONS) === true) {
                $inputSchema = self::closedSchema($tool::inputSchema());
                $description = $tool::description();
                $surface[$tool::NAME] = [
                    'description' => $description,
                    'inputSchema' => $inputSchema,
                ];
                $builder->addTool(
                    [
                        $tool,
                        'handle',
                    ],
                    $tool::NAME,
                    null,
                    $description,
                    null,
                    $inputSchema,
                );
            }
        }

        ksort($surface);
        $hasTools = empty($surface) === false;
        $builder->setEventDispatcher(new ToolListChangedNotifier(md5((string) json_encode($surface))));
        $builder->setCapabilities(new ServerCapabilities(
            tools: $hasTools,
            toolsListChanged: $hasTools,
            resources: false,
            resourcesSubscribe: false,
            resourcesListChanged: false,
            prompts: false,
            promptsListChanged: false,
            logging: true,
            completions: true,
        ));

        return $builder->build();
    }

    /**
     * Server instructions injected in the agent context at initialize time:
     * the entry point and the transverse conventions an agent needs before
     * its first call.
     */
    /**
     * A tool accepts what it declares, and nothing else. Left open, JSON Schema
     * lets an undeclared argument through, and the SDK drops it when mapping to
     * handle(): a misspelled call answers success and changes nothing.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private static function closedSchema(array $schema): array
    {
        if (isset($schema['properties']) === true && is_array($schema['properties']) === true) {
            $schema['additionalProperties'] = false;
            foreach ($schema['properties'] as $name => $property) {
                if (is_array($property) === true) {
                    $schema['properties'][$name] = self::closedSchema($property);
                }
            }
        }
        if (isset($schema['items']) === true && is_array($schema['items']) === true) {
            $schema['items'] = self::closedSchema($schema['items']);
        }

        return $schema;
    }

    private static function instructions(): string
    {
        return 'Blackcube content administration.'
            . "\n" . '- Start with info.model: it maps every entity (typed fields, relations, rules) and the tool surface.'
            . "\n" . '- Routes (dcore-XXX) are opaque ids: take them from list/search results, never build one.'
            . "\n" . '- Values shaped @store/path/file.ext are file references: read them with file.fetch, upload with file.upload.'
            . "\n" . '- Nothing is deleted where no delete verb exists: disable with update {active: false}.'
            . "\n" . '- Typical write flow: content.list -> content.get -> elasticSchema.list {kind: bloc} for the bloc schemas allowed by the node type -> content.update (the blocs list is complete: send every bloc from content.get and edit only the ones to change, omitted blocs are deleted).';
    }
}
