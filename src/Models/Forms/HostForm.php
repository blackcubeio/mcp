<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Models\Forms;

use Blackcube\BridgeFormModel\BridgeFormModel;
use Blackcube\BridgeFormModel\Attributes\Bridge;
use Blackcube\Mcp\Traits\PatchScenarioTrait;
use Yiisoft\Validator\Rule\BooleanValue;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Result;

/**
 * Host form model (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class HostForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected string $name = '';
    protected ?string $siteName = null;
    protected ?string $siteAlternateName = null;
    protected ?string $siteDescription = null;
    protected bool $active = false;

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
    #[Bridge]
    public function setSiteName(?string $siteName): void
    {
        $this->siteName = $siteName;
    }
    #[Bridge]
    public function getSiteName(): ?string
    {
        return $this->siteName;
    }
    #[Bridge]
    public function setSiteAlternateName(?string $siteAlternateName): void
    {
        $this->siteAlternateName = $siteAlternateName;
    }
    #[Bridge]
    public function getSiteAlternateName(): ?string
    {
        return $this->siteAlternateName;
    }
    #[Bridge]
    public function setSiteDescription(?string $siteDescription): void
    {
        $this->siteDescription = $siteDescription;
    }
    #[Bridge]
    public function getSiteDescription(): ?string
    {
        return $this->siteDescription;
    }
    #[Bridge]
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
    #[Bridge]
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * {@inheritdoc}
     */
    public function load(mixed $submittedValues, ?string $scope = null): bool
    {
        if ($this->getScenario() !== 'edit-protected') {
            $this->active = false;
        }
        return parent::load($submittedValues, $scope);
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return [
            'create' => [
                'name',
                'siteName',
                'siteAlternateName',
                'siteDescription',
                'active',
            ],
            'edit' => [
                'name',
                'siteName',
                'siteAlternateName',
                'siteDescription',
                'active',
            ],
            'edit-protected' => [
                'siteName',
                'siteAlternateName',
                'siteDescription',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'name' => [
                new Required(),
                new Length(max: 255),
                new Callback(callback: $this->checkHostname(...)),
            ],
            'siteName' => [
                new Length(max: 255),
            ],
            'siteAlternateName' => [
                new Length(max: 255),
            ],
            'siteDescription' => [
                new Length(max: 65535),
            ],
            'active' => [
                new BooleanValue(),
            ],
        ];
    }

    protected function checkHostname(mixed $hostname): Result
    {
        $validationResult = new Result();
        if (str_contains($hostname, '/') === true) {
            $validationResult->addError('Hostname must not contain "/" (e.g. localhost, blackcube.io)');
        }
        return $validationResult;
    }
}
