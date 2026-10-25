<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dcore\Models\SchemaSchema;
use Blackcube\Mcp\Models\Forms\SchemaSchemaForm;
use RuntimeException;

/**
 * Read and write logic for the SchemaSchema mappings: which xeo schema is
 * fed by which regular schema (page or bloc) and how the fields map — the
 * table driving the xeo refresh. Cheap to rebuild, hence a delete verb.
 * Returns plain data; raises a RuntimeException with a clear message on a
 * business error.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class SchemaMappingService
{
    /**
     * @var array<int, string>|null
     */
    private ?array $schemaNames = null;

    /**
     * @return array<string, mixed>
     */
    public function list(): array
    {
        $mappingsQuery = SchemaSchema::query()->orderBy([
            'regularElasticSchemaId' => SORT_ASC,
            'xeoElasticSchemaId' => SORT_ASC,
        ]);

        $items = [];
        /** @var SchemaSchema $schemaSchema */
        foreach ($mappingsQuery->each() as $schemaSchema) {
            $items[] = $this->item($schemaSchema);
        }

        return [
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function create(int $regularElasticSchemaId, int $xeoElasticSchemaId, string $mapping): array
    {
        $form = new SchemaSchemaForm();
        $form->setScenario('create');
        $form->load([
            'regularElasticSchemaId' => $regularElasticSchemaId,
            'xeoElasticSchemaId' => $xeoElasticSchemaId,
            'mapping' => $mapping,
        ], '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }
        $schemaSchema = new SchemaSchema();
        $form->populateModel($schemaSchema);
        $schemaSchema->save();

        return $this->item($schemaSchema);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $regularElasticSchemaId, int $xeoElasticSchemaId, string $mapping): array
    {
        $schemaSchema = SchemaSchema::query()
            ->andWhere([
                'regularElasticSchemaId' => $regularElasticSchemaId,
                'xeoElasticSchemaId' => $xeoElasticSchemaId,
            ])
            ->one();
        if ($schemaSchema === null) {
            throw new RuntimeException('SchemaSchema mapping not found for pair (' . $regularElasticSchemaId . ', ' . $xeoElasticSchemaId . ')');
        }
        $form = new SchemaSchemaForm();
        $form->setScenario('edit');
        $form->load([
            'mapping' => $mapping,
        ], '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }
        $form->populateModel($schemaSchema);
        $schemaSchema->save();

        return $this->item($schemaSchema);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(int $regularElasticSchemaId, int $xeoElasticSchemaId): array
    {
        $schemaSchema = SchemaSchema::query()
            ->andWhere([
                'regularElasticSchemaId' => $regularElasticSchemaId,
                'xeoElasticSchemaId' => $xeoElasticSchemaId,
            ])
            ->one();
        if ($schemaSchema === null) {
            throw new RuntimeException('SchemaSchema mapping not found for pair (' . $regularElasticSchemaId . ', ' . $xeoElasticSchemaId . ')');
        }
        $deleted = $this->item($schemaSchema);
        $schemaSchema->delete();

        return $deleted;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function schemaRef(int $schemaId): ?array
    {
        if ($this->schemaNames === null) {
            $this->schemaNames = [];
            $elasticSchemasQuery = ElasticSchema::query();
            /** @var ElasticSchema $elasticSchema */
            foreach ($elasticSchemasQuery->each() as $elasticSchema) {
                $this->schemaNames[$elasticSchema->getId()] = $elasticSchema->getName();
            }
        }

        return [
            'id' => $schemaId,
            'name' => $this->schemaNames[$schemaId] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(SchemaSchema $schemaSchema): array
    {
        $mapping = $schemaSchema->getMapping();

        return [
            'regularSchema' => $this->schemaRef($schemaSchema->getRegularElasticSchemaId()),
            'xeoSchema' => $this->schemaRef($schemaSchema->getXeoElasticSchemaId()),
            'mapping' => $mapping !== null ? json_decode($mapping, true) : null,
        ];
    }
}
