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
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;

/**
 * Sitemap form model (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class SitemapForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected ?int $id = null;
    protected string $frequency = 'daily';
    protected float $priority = 0.5;
    protected bool $active = false;

    /**
     * {@inheritdoc}
     */
    public function load(mixed $submittedValues, ?string $scope = null): bool
    {
        $this->active = false;
        return parent::load($submittedValues, $scope);
    }

    #[Bridge]
    public function setId(?int $id): void
    {
        $this->id = $id;
    }
    #[Bridge]
    public function getId(): ?int
    {
        return $this->id;
    }
    #[Bridge]
    public function setFrequency(string $frequency): void
    {
        $this->frequency = $frequency;
    }
    #[Bridge]
    public function getFrequency(): string
    {
        return $this->frequency;
    }
    #[Bridge]
    public function setPriority(float $priority): void
    {
        $this->priority = $priority;
    }
    #[Bridge]
    public function getPriority(): float
    {
        return $this->priority;
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
    public function scenarios(): array
    {
        return [
            'create' => [
                'frequency',
                'priority',
                'active',
            ],
            'edit' => [
                'frequency',
                'priority',
                'active',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'frequency' => [
                new Required(),
                new In([
                    'always',
                    'hourly',
                    'daily',
                    'weekly',
                    'monthly',
                    'yearly',
                    'never',
                ]),
            ],
            'priority' => [
                new Required(),
                new Number(min: 0.0, max: 1.0),
            ],
            'active' => [
                new BooleanValue(),
            ],
        ];
    }
}
