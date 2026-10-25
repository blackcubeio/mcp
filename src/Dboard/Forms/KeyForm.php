<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Dboard\Forms;

use Blackcube\BridgeFormModel\Attributes\Bridge;
use Blackcube\Dboard\Models\Forms\BridgeFormModel;
use Blackcube\Mcp\Dboard\McpAddon;
use Yiisoft\Validator\Rule\Date\Date;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

/**
 * Form for an MCP key. Scenario "create": a name, the granted scopes and an
 * optional TTL (capped by the service). Scenario "edit": the name, scopes and
 * the expiration date, without touching the token.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class KeyForm extends BridgeFormModel
{
    protected ?string $translateCategory = 'mcp';

    protected string $name = '';
    protected ?int $ttlDays = null;
    protected ?string $dateExpire = null;
    protected array $scopes = [];

    #[Bridge]
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    #[Bridge]
    public function getName(): string
    {
        return $this->name;
    }
    public function setTtlDays(?int $ttlDays): void
    {
        $this->ttlDays = null;
        if (empty($ttlDays) === false) {
            $this->ttlDays = $ttlDays;
        }
    }
    public function getTtlDays(): ?int
    {
        return $this->ttlDays;
    }
    #[Bridge(format: 'Y-m-d')]
    public function setDateExpire(?string $dateExpire): void
    {
        $this->dateExpire = null;
        if (empty($dateExpire) === false) {
            $this->dateExpire = $dateExpire;
        }
    }
    #[Bridge(format: 'Y-m-d')]
    public function getDateExpire(): ?string
    {
        return $this->dateExpire;
    }
    /**
     * @param string[] $scopes
     */
    #[Bridge]
    public function setScopes(array $scopes): void
    {
        $this->scopes = $scopes;
    }
    /**
     * @return string[]
     */
    #[Bridge]
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function scenarios(): array
    {
        return [
            'create' => [
                'name',
                'ttlDays',
                'scopes',
            ],
            'edit' => [
                'name',
                'dateExpire',
                'scopes',
            ],
        ];
    }

    public function rules(): array
    {
        return [
            'name' => [
                new Required(),
                new Length(max: 255),
            ],
            'ttlDays' => [
                new Integer(min: 1, skipOnEmpty: true),
            ],
            'dateExpire' => [
                new Date(format: 'php:Y-m-d', skipOnEmpty: true),
            ],
            'scopes' => [
                new Required(),
                new Each([
                    new In(McpAddon::getScopes()),
                ]),
            ],
        ];
    }

    protected function getRawLabels(): array
    {
        return [
            'name' => 'Name',
            'ttlDays' => 'TTL (days, optional)',
            'dateExpire' => 'Expires',
            'scopes' => 'Scopes',
        ];
    }
}
