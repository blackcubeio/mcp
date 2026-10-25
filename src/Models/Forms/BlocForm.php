<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Models\Forms;

use Blackcube\Dboard\Enums\ReorderMode;
use Blackcube\BridgeFormModel\BridgeFormModel;
use Blackcube\BridgeFormModel\Attributes\Bridge;
use Yiisoft\Validator\Rule\BooleanValue;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;

/**
 * Bloc form model with elastic properties support (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class BlocForm extends BridgeFormModel
{
    protected ?int $id = null;
    protected bool $active = false;
    protected ?int $elasticSchemaId = null;
    protected ?int $blocAdd = null;
    protected ?int $blocId = null;
    protected ?string $mode = null;
    protected ?array $order = null;

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
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
    #[Bridge]
    public function isActive(): bool
    {
        return $this->active;
    }
    #[Bridge]
    public function setElasticSchemaId(?int $elasticSchemaId): void
    {
        $this->elasticSchemaId = $elasticSchemaId;
    }
    #[Bridge]
    public function getElasticSchemaId(): ?int
    {
        return $this->elasticSchemaId;
    }
    public function setBlocAdd(?int $blocAdd): void
    {
        $this->blocAdd = $blocAdd;
    }
    public function getBlocAdd(): ?int
    {
        return $this->blocAdd;
    }
    public function setBlocId(?int $blocId): void
    {
        $this->blocId = $blocId;
    }
    public function getBlocId(): ?int
    {
        return $this->blocId;
    }
    public function setMode(?string $mode): void
    {
        $this->mode = $mode;
    }
    public function getMode(): ?string
    {
        return $this->mode;
    }
    public function setOrder(?array $order): void
    {
        $this->order = $order;
    }
    public function getOrder(): ?array
    {
        return $this->order;
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return [
            'edit' => [
                'active',
                self::ALL_ELASTIC_ATTRIBUTES,
            ],
            'add' => [
                'elasticSchemaId',
                'blocAdd',
            ],
            'move' => [
                'blocId',
                'mode',
            ],
            'dnd' => [
                'order',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'active' => [
                new BooleanValue(),
            ],
            'elasticSchemaId' => [
                new Integer(min: 1),
            ],
            'blocAdd' => [
                new Integer(min: 1, skipOnEmpty: true),
            ],
            'blocId' => [
                new Integer(min: 1),
            ],
            'mode' => [
                new In([
                    ReorderMode::MoveUp->value,
                    ReorderMode::MoveDown->value,
                ], skipOnEmpty: true),
            ],
            'order' => [
                new Each([
                    new Integer(min: 1),
                ], skipOnEmpty: true),
            ],
        ];
    }
}
