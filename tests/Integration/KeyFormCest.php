<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Integration;

use Blackcube\Mcp\Dboard\Forms\KeyForm;
use Blackcube\Mcp\Dboard\McpRbac;
use Blackcube\Mcp\Tests\Support\IntegrationTester;

/**
 * Integration tests for KeyForm validation (create / edit scenarios).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class KeyFormCest
{
    public function testValidCreatePasses(IntegrationTester $I): void
    {
        $I->wantTo('verify KeyForm passes validation with valid create data');
        $form = new KeyForm();
        $form->setScenario('create');
        $form->load([
            'name' => 'agent',
            'ttlDays' => 30,
            'scopes' => [
                McpRbac::PERMISSION_MCP_CONNECT,
            ],
        ], '');

        $I->assertTrue($form->validate());
    }

    public function testEmptyNameFails(IntegrationTester $I): void
    {
        $I->wantTo('verify KeyForm fails validation with an empty name');
        $form = new KeyForm();
        $form->setScenario('create');
        $form->load([
            'name' => '',
            'scopes' => [
                McpRbac::PERMISSION_MCP_CONNECT,
            ],
        ], '');

        $I->assertFalse($form->validate());
    }

    public function testUnknownScopeFails(IntegrationTester $I): void
    {
        $I->wantTo('verify KeyForm fails validation with an unknown scope');
        $form = new KeyForm();
        $form->setScenario('create');
        $form->load([
            'name' => 'agent',
            'scopes' => [
                'not:a:scope',
            ],
        ], '');

        $I->assertFalse($form->validate());
    }

    public function testEmptyScopesFails(IntegrationTester $I): void
    {
        $I->wantTo('verify KeyForm requires at least one scope');
        $form = new KeyForm();
        $form->setScenario('create');
        $form->load([
            'name' => 'agent',
            'scopes' => [],
        ], '');

        $I->assertFalse($form->validate());
    }

    public function testEditScenarioAcceptsDateExpire(IntegrationTester $I): void
    {
        $I->wantTo('verify KeyForm edit scenario binds name/scopes/dateExpire');
        $form = new KeyForm();
        $form->setScenario('edit');
        $form->load([
            'name' => 'agent',
            'dateExpire' => '2026-12-01',
            'scopes' => [
                McpRbac::PERMISSION_MCP_CONNECT,
            ],
        ], '');

        $I->assertTrue($form->validate());
        $I->assertEquals('agent', $form->getName());
        $I->assertEquals([
            McpRbac::PERMISSION_MCP_CONNECT,
        ], $form->getScopes());
        $I->assertEquals('2026-12-01', $form->getDateExpire());
    }
}
