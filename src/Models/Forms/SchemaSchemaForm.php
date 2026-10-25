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
use Blackcube\Dcore\Models\SchemaSchema;
use Blackcube\Mcp\Traits\PatchScenarioTrait;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

/**
 * Validation and hydration of a SchemaSchema (the mapping driving the xeo
 * refresh: which xeo schema is fed by which regular schema, and how the
 * fields map). Same rules as the dboard SchemaSchemaForm: unique pair on
 * create, JSON mapping.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class SchemaSchemaForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected ?int $regularElasticSchemaId = null;
    protected ?int $xeoElasticSchemaId = null;
    protected string $mapping = '';

    #[Bridge]
    public function setRegularElasticSchemaId(?int $regularElasticSchemaId): void
    {
        $this->regularElasticSchemaId = $regularElasticSchemaId;
    }
    #[Bridge]
    public function getRegularElasticSchemaId(): ?int
    {
        return $this->regularElasticSchemaId;
    }
    #[Bridge]
    public function setXeoElasticSchemaId(?int $xeoElasticSchemaId): void
    {
        $this->xeoElasticSchemaId = $xeoElasticSchemaId;
    }
    #[Bridge]
    public function getXeoElasticSchemaId(): ?int
    {
        return $this->xeoElasticSchemaId;
    }
    #[Bridge]
    public function setMapping(string $mapping): void
    {
        $this->mapping = $mapping;
    }
    #[Bridge]
    public function getMapping(): string
    {
        return $this->mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return [
            'create' => [
                'regularElasticSchemaId',
                'xeoElasticSchemaId',
                'mapping',
            ],
            'edit' => [
                'mapping',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $rules = [
            'regularElasticSchemaId' => [
                new Required(),
                new Integer(min: 1),
            ],
            'xeoElasticSchemaId' => [
                new Required(),
                new Integer(min: 1),
            ],
            'mapping' => [
                new Required(),
                new Length(max: 65535),
                new Callback(callback: $this->checkMappingJson(...)),
            ],
        ];

        if ($this->getScenario() === 'create') {
            $rules['xeoElasticSchemaId'][] = new Callback(callback: $this->checkUniqueAssociation(...));
        }

        return $rules;
    }

    protected function checkMappingJson(mixed $mapping): Result
    {
        $validationResult = new Result();
        if ($mapping !== '' && json_decode((string) $mapping) === null && json_last_error() !== JSON_ERROR_NONE) {
            $validationResult->addError('Mapping must be valid JSON.');
        }
        return $validationResult;
    }

    protected function checkUniqueAssociation(): Result
    {
        $validationResult = new Result();
        $associationExists = SchemaSchema::query()
            ->andWhere([
                'regularElasticSchemaId' => (int) $this->regularElasticSchemaId,
                'xeoElasticSchemaId' => (int) $this->xeoElasticSchemaId,
            ])
            ->exists();
        if ($associationExists === true) {
            $validationResult->addError('This association already exists.');
        }
        return $validationResult;
    }
}
