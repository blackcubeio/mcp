<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Dboard;

use Blackcube\Dboard\Addons\AddonOauth2Interface;
use Blackcube\Dboard\Addons\AddonRbacInterface;
use Blackcube\Dboard\Addons\AddonRoutesInterface;
use Blackcube\Dboard\Addons\AddonSidebarInterface;
use Blackcube\Dboard\Middlewares\JwtAuthMiddleware;
use Blackcube\Mcp\Dboard\Handlers\Keys\Create as KeysCreate;
use Blackcube\Mcp\Dboard\Handlers\Keys\Delete as KeysDelete;
use Blackcube\Mcp\Dboard\Handlers\Keys\DeleteInit as KeysDeleteInit;
use Blackcube\Mcp\Dboard\Handlers\Keys\Edit as KeysEdit;
use Blackcube\Mcp\Dboard\Handlers\Keys\Index as KeysIndex;
use Blackcube\Mcp\Dboard\Handlers\Keys\Rotate as KeysRotate;
use Blackcube\Mcp\Dboard\Handlers\Keys\RotateInit as KeysRotateInit;
use Blackcube\Mcp\Mcp\Tools\Registry;
use Blackcube\Bleet\Bleet;
use Yiisoft\Rbac\ManagerInterface;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The MCP admin addon for dboard: contributes its RBAC, its sidebar entry, its
 * key-management routes and its OAuth2 population. dboard discovers it through
 * its config 'addons'.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class McpAddon implements AddonRbacInterface, AddonSidebarInterface, AddonRoutesInterface, AddonOauth2Interface
{
    public const POPULATION = 'mcp';

    public static function getRbac(): string
    {
        return McpRbac::class;
    }

    public static function getPopulation(): string
    {
        return self::POPULATION;
    }

    /**
     * The permissions a client can be granted on the MCP server: the right to
     * connect, and the dboard permissions the tools gate on.
     *
     * @return string[]
     */
    public static function getScopes(): array
    {
        $scopes = [
            McpRbac::PERMISSION_MCP_CONNECT,
        ];
        foreach (Registry::TOOLS as $tool) {
            $scopes = array_merge($scopes, $tool::PERMISSIONS);
        }
        return array_values(array_unique($scopes));
    }

    public static function getSidebar(
        ?TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        ManagerInterface $rbacManager,
        string $userId,
        ?string $currentRoute,
    ): array {
        $items = [];
        if ($rbacManager->userHasPermission($userId, McpRbac::PERMISSION_MCP_VIEW) === true) {
            $label = $translator?->translate('MCP keys', category: 'mcp') ?? 'MCP keys';
            $sidebarItem = Bleet::sidebarItem($label)
                ->outline('key')
                ->url($urlGenerator->generate('mcp.keys'));
            if ($currentRoute !== null && str_starts_with($currentRoute, 'mcp.keys') === true) {
                $sidebarItem = $sidebarItem->active();
            }
            $items[] = $sidebarItem;
        }

        return $items;
    }

    public static function getRoutesGroup(): Group
    {
        return Group::create('/mcp')
            ->routes(
                Route::get('/keys')
                    ->middleware(JwtAuthMiddleware::withRbac([
                        McpRbac::PERMISSION_MCP_VIEW,
                    ]))
                    ->action(KeysIndex::class)
                    ->name('mcp.keys'),
                Route::methods([
                    'GET',
                    'POST',
                ], '/keys/create')
                    ->middleware(JwtAuthMiddleware::withRbac([
                        McpRbac::PERMISSION_MCP_CREATE,
                    ]))
                    ->action(KeysCreate::class)
                    ->name('mcp.keys.create'),
                Route::methods([
                    'GET',
                    'POST',
                ], '/keys/{id}/edit')
                    ->middleware(JwtAuthMiddleware::withRbac([
                        McpRbac::PERMISSION_MCP_UPDATE,
                    ]))
                    ->action(KeysEdit::class)
                    ->name('mcp.keys.edit'),
                Route::get('/keys/{id}/delete')
                    ->middleware(JwtAuthMiddleware::withRbac([
                        McpRbac::PERMISSION_MCP_DELETE,
                    ]))
                    ->action(KeysDeleteInit::class)
                    ->name('mcp.keys.delete'),
                Route::delete('/keys/{id}/delete')
                    ->middleware(JwtAuthMiddleware::withRbac([
                        McpRbac::PERMISSION_MCP_DELETE,
                    ]))
                    ->action(KeysDelete::class),
                Route::get('/keys/{id}/rotate')
                    ->middleware(JwtAuthMiddleware::withRbac([
                        McpRbac::PERMISSION_MCP_UPDATE,
                    ]))
                    ->action(KeysRotateInit::class)
                    ->name('mcp.keys.rotate'),
                Route::post('/keys/{id}/rotate')
                    ->middleware(JwtAuthMiddleware::withRbac([
                        McpRbac::PERMISSION_MCP_UPDATE,
                    ]))
                    ->action(KeysRotate::class),
            );
    }
}
