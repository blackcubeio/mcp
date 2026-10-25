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
use Yiisoft\Validator\Rule\Length;

/**
 * Validation and hydration of a GlobalXeo (site-wide SEO/GEO data of a host:
 * Organization, WebSite, Robots, Sitemap). The dynamic payload goes through
 * the 'elastic' scenario, validated by the builtin schema of the kind.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class GlobalXeoForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected ?int $id = null;
    protected ?string $name = null;
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
    public function setName(?string $name): void
    {
        $this->name = $name;
    }
    #[Bridge]
    public function getName(): ?string
    {
        return $this->name;
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
            'edit' => [
                'name',
                'active',
            ],
            'elastic' => [
                self::ALL_ELASTIC_ATTRIBUTES,
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
                new Length(max: 255, skipOnEmpty: true),
            ],
            'active' => [
                new BooleanValue(),
            ],
        ];
    }
}
