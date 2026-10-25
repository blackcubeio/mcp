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
use Blackcube\Dcore\Models\Parameter;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Result;

/**
 * Parameter form model (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ParameterForm extends BridgeFormModel
{
    protected string $domain = '';
    protected string $name = '';
    protected ?string $value = null;

    #[Bridge]
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }
    #[Bridge]
    public function getDomain(): string
    {
        return $this->domain;
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
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }
    #[Bridge]
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return [
            'create' => [
                'domain',
                'name',
                'value',
            ],
            'edit' => [
                'domain',
                'name',
                'value',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $rules = [
            'domain' => [
                new Required(),
                new Length(max: 255),
            ],
            'name' => [
                new Required(),
                new Length(max: 255),
            ],
            'value' => [
                new Length(max: 65535),
            ],
        ];

        if ($this->getScenario() === 'create') {
            $rules['name'][] = new Callback(callback: $this->checkUniqueParameter(...));
        }

        return $rules;
    }

    protected function checkUniqueParameter(): Result
    {
        $validationResult = new Result();
        $parameterExists = Parameter::query()
            ->andWhere([
                'domain' => $this->domain,
                'name' => $this->name,
            ])
            ->exists();
        if ($parameterExists === true) {
            $validationResult->addError('A parameter with this domain and name already exists.');
        }
        return $validationResult;
    }
}
