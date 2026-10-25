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
use Blackcube\Dcore\Models\Language;
use Blackcube\Mcp\Traits\PatchScenarioTrait;
use Yiisoft\Validator\Rule\BooleanValue;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Result;

/**
 * Language form model (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class LanguageForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected string $id = '';
    protected string $name = '';
    protected bool $active = false;
    protected bool $main = false;

    #[Bridge]
    public function setId(string $id): void
    {
        $this->id = $id;
    }
    #[Bridge]
    public function getId(): string
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
    public function setMain(bool $main): void
    {
        $this->main = $main;
    }
    #[Bridge]
    public function isMain(): bool
    {
        return $this->main;
    }

    /**
     * {@inheritdoc}
     */
    public function load(mixed $submittedValues, ?string $scope = null): bool
    {
        $this->active = false;
        $this->main = false;
        return parent::load($submittedValues, $scope);
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return [
            'create' => [
                'id',
                'name',
                'active',
                'main',
            ],
            'edit' => [
                'id',
                'name',
                'active',
                'main',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $rules = [
            'id' => [
                new Required(),
                new Length(max: 8),
            ],
            'name' => [
                new Required(),
                new Length(max: 255),
            ],
            'active' => [
                new BooleanValue(),
            ],
            'main' => [
                new BooleanValue(),
            ],
        ];

        if ($this->getScenario() === 'create') {
            $rules['id'][] = new Callback(callback: $this->checkUniqueLanguage(...));
        }

        return $rules;
    }

    protected function checkUniqueLanguage(): Result
    {
        $validationResult = new Result();
        $languageExists = Language::query()
            ->andWhere([
                'id' => $this->id,
            ])
            ->exists();
        if ($languageExists === true) {
            $validationResult->addError('A language with this id already exists.');
        }
        return $validationResult;
    }
}
