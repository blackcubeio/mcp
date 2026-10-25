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
use Yiisoft\Validator\Rule\StringValue;

/**
 * Menu form model (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class MenuForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected ?int $id = null;
    protected string $name = '';
    protected int $hostId = 1;
    protected string $languageId = '';
    protected ?string $route = null;
    protected ?string $queryString = null;
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
    public function setHostId(int $hostId): void
    {
        $this->hostId = $hostId;
    }
    #[Bridge]
    public function getHostId(): int
    {
        return $this->hostId;
    }
    #[Bridge]
    public function setLanguageId(string $languageId): void
    {
        $this->languageId = $languageId;
    }
    #[Bridge]
    public function getLanguageId(): string
    {
        return $this->languageId;
    }
    #[Bridge]
    public function setRoute(?string $route): void
    {
        $this->route = $route;
    }
    #[Bridge]
    public function getRoute(): ?string
    {
        return $this->route;
    }
    #[Bridge]
    public function setQueryString(?string $queryString): void
    {
        $this->queryString = $queryString;
    }
    #[Bridge]
    public function getQueryString(): ?string
    {
        return $this->queryString;
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
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return [
            'create' => [
                'name',
                'hostId',
                'languageId',
                'route',
                'queryString',
                'active',
                'move',
                'moveMode',
                'moveTargetId',
            ],
            'edit' => [
                'name',
                'hostId',
                'languageId',
                'route',
                'queryString',
                'active',
                'move',
                'moveMode',
                'moveTargetId',
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
            ],
            'languageId' => [
                new Required(),
            ],
            'hostId' => [
                new Required(),
                new Integer(min: 1),
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
            'route' => [
                new StringValue(skipOnEmpty: true),
            ],
            'queryString' => [
                new StringValue(skipOnEmpty: true),
            ],
        ];
    }
}
