<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Models\Forms;

use Blackcube\Dcore\Enums\ElasticSchemaKind;
use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\BridgeFormModel\BridgeFormModel;
use Blackcube\BridgeFormModel\Attributes\Bridge;
use Blackcube\Mcp\Traits\PatchScenarioTrait;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\BooleanValue;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

/**
 * ElasticSchema form model (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ElasticSchemaForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected ?int $id = null;
    protected string $name = '';
    protected ?string $schema = '{"type": "object","properties": {},"required": []}';
    protected ?string $view = null;
    protected ?string $mdMapping = null;
    protected string $kind = 'common';
    protected bool $builtin = false;
    protected bool $active = true;

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
    public function setSchema(?string $schema): void
    {
        $this->schema = $schema;
    }
    #[Bridge]
    public function getSchema(): ?string
    {
        return $this->schema;
    }
    #[Bridge]
    public function setView(?string $view): void
    {
        $this->view = $view;
    }
    #[Bridge]
    public function getView(): ?string
    {
        return $this->view;
    }
    #[Bridge]
    public function setMdMapping(?string $mdMapping): void
    {
        $this->mdMapping = $mdMapping;
    }
    #[Bridge]
    public function getMdMapping(): ?string
    {
        return $this->mdMapping;
    }
    #[Bridge]
    public function setKind(string $kind): void
    {
        $this->kind = $kind;
    }
    #[Bridge]
    public function getKind(): string
    {
        return $this->kind;
    }
    #[Bridge]
    public function setBuiltin(bool $builtin): void
    {
        $this->builtin = $builtin;
    }
    #[Bridge]
    public function isBuiltin(): bool
    {
        return $this->builtin;
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
    public function load(mixed $submittedValues, ?string $formName = null): bool
    {
        $this->active = false;
        return parent::load($submittedValues, $formName);
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return [
            'create' => [
                'name',
                'schema',
                'view',
                'mdMapping',
                'kind',
                'active',
            ],
            'edit' => [
                'name',
                'schema',
                'view',
                'mdMapping',
                'kind',
                'active',
            ],
            'builtin' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $kindValues = [];
        foreach (ElasticSchemaKind::cases() as $schemaKind) {
            $kindValues[] = $schemaKind->value;
        }
        return [
            'name' => [
                new Required(),
                new Length(max: 255),
                new Callback(callback: $this->checkUniqueName(...)),
            ],
            'schema' => [
                new Length(max: 65535),
            ],
            'view' => [
                new Length(max: 255, skipOnEmpty: true),
            ],
            'mdMapping' => [
                new Length(max: 65535, skipOnEmpty: true),
            ],
            'kind' => [
                new Required(),
                new In($kindValues),
            ],
            'builtin' => [
                new BooleanValue(),
            ],
            'active' => [
                new BooleanValue(),
            ],
        ];
    }

    protected function checkUniqueName(mixed $name): Result
    {
        $validationResult = new Result();
        $elasticSchemasQuery = ElasticSchema::query()
            ->andWhere([
                'name' => $name,
            ])
            ->andWhere([
                'kind' => $this->getKind(),
            ]);
        if ($this->getId() !== null) {
            $elasticSchemasQuery->andWhere([
                '!=',
                'id',
                $this->getId(),
            ]);
        }
        if ($elasticSchemasQuery->exists() === true) {
            $validationResult->addError('An elastic schema with this name already exists for this kind.');
        }
        return $validationResult;
    }
}
