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
use Yiisoft\Validator\Rule\Required;

/**
 * Type form model (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TypeForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected string $name = '';
    protected ?string $handler = null;
    protected bool $contentAllowed = false;
    protected bool $tagAllowed = false;

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
    public function setHandler(?string $handler): void
    {
        $this->handler = $handler;
    }
    #[Bridge]
    public function getHandler(): ?string
    {
        return $this->handler;
    }
    #[Bridge]
    public function setContentAllowed(bool $contentAllowed): void
    {
        $this->contentAllowed = $contentAllowed;
    }
    #[Bridge]
    public function isContentAllowed(): bool
    {
        return $this->contentAllowed;
    }
    #[Bridge]
    public function setTagAllowed(bool $tagAllowed): void
    {
        $this->tagAllowed = $tagAllowed;
    }
    #[Bridge]
    public function isTagAllowed(): bool
    {
        return $this->tagAllowed;
    }

    /**
     * {@inheritdoc}
     */
    public function load(mixed $submittedValues, ?string $scope = null): bool
    {
        $this->contentAllowed = false;
        $this->tagAllowed = false;
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
                'handler',
                'contentAllowed',
                'tagAllowed',
            ],
            'edit' => [
                'name',
                'handler',
                'contentAllowed',
                'tagAllowed',
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
            'handler' => [
                new Length(max: 255),
            ],
            'contentAllowed' => [
                new BooleanValue(),
            ],
            'tagAllowed' => [
                new BooleanValue(),
            ],
        ];
    }
}
