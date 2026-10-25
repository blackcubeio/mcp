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
 * Validation and hydration of a LlmMenu node (llms.txt tree) for the mcp
 * tools. The target element arrives as contentId OR tagId, already converted
 * from the route by LlmMenuService.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class LlmMenuForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected ?int $id = null;
    protected string $name = '';
    protected ?string $description = null;
    protected ?int $contentId = null;
    protected ?int $tagId = null;

    protected bool $move = false;
    protected string $moveMode = TreePosition::Into->value;
    protected ?int $moveTargetId = null;

    /**
     * {@inheritdoc}
     */
    public function load(mixed $submittedValues, ?string $scope = null): bool
    {
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
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
    #[Bridge]
    public function getDescription(): ?string
    {
        return $this->description;
    }
    #[Bridge]
    public function setContentId(?int $contentId): void
    {
        $this->contentId = $contentId;
    }
    #[Bridge]
    public function getContentId(): ?int
    {
        return $this->contentId;
    }
    #[Bridge]
    public function setTagId(?int $tagId): void
    {
        $this->tagId = $tagId;
    }
    #[Bridge]
    public function getTagId(): ?int
    {
        return $this->tagId;
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
                'description',
                'contentId',
                'tagId',
                'move',
                'moveMode',
                'moveTargetId',
            ],
            'edit' => [
                'name',
                'description',
                'contentId',
                'tagId',
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
            'description' => [
                new StringValue(skipOnEmpty: true),
            ],
            'contentId' => [
                new Integer(skipOnEmpty: true),
            ],
            'tagId' => [
                new Integer(skipOnEmpty: true),
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
        ];
    }
}
