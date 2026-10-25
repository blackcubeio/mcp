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
use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\Mcp\Traits\PatchScenarioTrait;
use Yiisoft\Validator\Rule\BooleanValue;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

/**
 * Shared form model for routable elements (content and tag): the node fields,
 * the dynamic data ('elastic' scenario) and the tree placement, all identical
 * between content and tag. Concrete forms add only what differs through
 * extraFields()/extraRules() (e.g. the content publication window).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractElementForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected ?int $id = null;
    protected ?string $name = null;
    protected ?string $languageId = null;
    protected ?int $typeId = null;
    protected ?int $elasticSchemaId = null;
    protected bool $active = false;

    protected bool $move = false;
    protected string $moveMode = TreePosition::Into->value;
    protected ?int $moveTargetId = null;

    /**
     * {@inheritdoc}
     */
    public function load(mixed $submittedValues, ?string $scope = null): bool
    {
        $this->active = false;
        $this->move = false;
        $loaded = parent::load($submittedValues, $scope);
        if ($this->typeId === 0) {
            $this->typeId = null;
        }
        if ($this->elasticSchemaId === 0) {
            $this->elasticSchemaId = null;
        }
        return $loaded;
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
    public function setLanguageId(?string $languageId): void
    {
        $this->languageId = $languageId;
    }
    #[Bridge]
    public function getLanguageId(): ?string
    {
        return $this->languageId;
    }
    #[Bridge]
    public function setTypeId(?int $typeId): void
    {
        $this->typeId = null;
        if (empty($typeId) === false) {
            $this->typeId = $typeId;
        }
    }
    #[Bridge]
    public function getTypeId(): ?int
    {
        return $this->typeId;
    }
    #[Bridge]
    public function setElasticSchemaId(?int $elasticSchemaId): void
    {
        $this->elasticSchemaId = null;
        if (empty($elasticSchemaId) === false) {
            $this->elasticSchemaId = $elasticSchemaId;
        }
    }
    #[Bridge]
    public function getElasticSchemaId(): ?int
    {
        return $this->elasticSchemaId;
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
    public function setMove(bool $move): void
    {
        $this->move = $move;
    }
    public function isMove(): bool
    {
        return $this->move;
    }
    public function setMoveMode(string $moveMode): void
    {
        $this->moveMode = $moveMode;
    }
    public function getMoveMode(): string
    {
        return $this->moveMode;
    }
    public function setMoveTargetId(?int $moveTargetId): void
    {
        $this->moveTargetId = $moveTargetId;
    }
    public function getMoveTargetId(): ?int
    {
        return $this->moveTargetId;
    }

    /**
     * Extra scenario fields a concrete form adds between the node fields and the
     * placement fields (e.g. the content publication window).
     *
     * @return string[]
     */
    protected function extraFields(): array
    {
        return [];
    }

    /**
     * Extra validation rules a concrete form adds for its extraFields().
     *
     * @return array<string, mixed>
     */
    protected function extraRules(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        $fields = array_merge(
            [
                'name',
                'languageId',
                'typeId',
                'elasticSchemaId',
                'active',
            ],
            $this->extraFields(),
            [
                'move',
                'moveMode',
                'moveTargetId',
            ],
        );

        return [
            'create' => $fields,
            'edit' => $fields,
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
        return array_merge(
            [
                'name' => [
                    new Required(),
                    new Length(max: 255),
                ],
                'languageId' => [
                    new Required(),
                ],
                'typeId' => [
                    new Integer(skipOnEmpty: true),
                ],
                'elasticSchemaId' => [
                    new Integer(skipOnEmpty: true),
                ],
                'active' => [
                    new BooleanValue(),
                ],
                'move' => [
                    new BooleanValue(skipOnEmpty: true),
                ],
                'moveMode' => [
                    new In([
                        TreePosition::Before->value,
                        TreePosition::Into->value,
                        TreePosition::After->value,
                    ]),
                ],
                'moveTargetId' => [
                    new Integer(skipOnEmpty: true),
                ],
            ],
            $this->extraRules(),
        );
    }
}
