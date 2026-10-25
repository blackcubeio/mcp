<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dcore\Models\Type;
use Blackcube\Dcore\Models\TypeElasticSchema;
use Blackcube\Mcp\Models\Forms\TypeForm;
use RuntimeException;
use Throwable;

/**
 * Read and write logic for the Types (page types of contents/tags) and the
 * ElasticSchemas their blocs may use. Returns plain data; raises a
 * RuntimeException with a clear message on a business error (not found,
 * unknown schema ids, invalid fields), which the tools translate into a
 * CallToolResult error.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TypeService
{
    /**
     * @return array<string, mixed>
     */
    public function list(): array
    {
        $items = [];
        $typesQuery = Type::query()->with([
            'elasticSchemas',
        ])->orderBy([
            'name' => SORT_ASC,
            'id' => SORT_ASC,
        ]);
        /** @var Type $type */
        foreach ($typesQuery->each() as $type) {
            $items[] = $this->item($type);
        }

        return [
            'items' => $items,
        ];
    }

    /**
     * @param array<int, int>|null $elasticSchemaIds
     * @return array<string, mixed>
     */
    public function create(string $name, ?string $handler, bool $contentAllowed, bool $tagAllowed, ?array $elasticSchemaIds): array
    {
        $this->assertElasticSchemaIds($elasticSchemaIds);
        $provided = [
            'name' => $name,
            'contentAllowed' => $contentAllowed,
            'tagAllowed' => $tagAllowed,
        ];
        if ($handler !== null) {
            $provided['handler'] = $handler;
        }
        $form = new TypeForm();
        $form->setScenario('create');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }

        $type = new Type();
        $transaction = $type->db()->beginTransaction();
        try {
            $form->populateModel($type);
            $type->save();
            if ($elasticSchemaIds !== null) {
                $this->linkElasticSchemas($type, $elasticSchemaIds);
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $createdTypeQuery = Type::query()->andWhere([
            'id' => $type->getId(),
        ])->with([
            'elasticSchemas',
        ]);

        return $this->item($createdTypeQuery->one());
    }

    /**
     * Updates the provided fields only; elasticSchemaIds replaces the whole
     * list of allowed schemas ([] clears it).
     *
     * @param array<int, int>|null $elasticSchemaIds
     * @return array<string, mixed>
     */
    public function update(int $id, ?string $name, ?bool $contentAllowed, ?bool $tagAllowed, ?array $elasticSchemaIds): array
    {
        $type = Type::query()->andWhere([
            'id' => $id,
        ])->one();
        if ($type === null) {
            throw new RuntimeException('Type not found: ' . $id);
        }
        $this->assertElasticSchemaIds($elasticSchemaIds);

        $provided = [];
        if ($name !== null) {
            $provided['name'] = $name;
        }
        if ($contentAllowed !== null) {
            $provided['contentAllowed'] = $contentAllowed;
        }
        if ($tagAllowed !== null) {
            $provided['tagAllowed'] = $tagAllowed;
        }
        $form = new TypeForm();
        $form->setScenario('edit');
        $form->setPatchFields(array_keys($provided));
        $form->load($provided, '');
        if ($form->validate() === false) {
            throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
        }

        $transaction = $type->db()->beginTransaction();
        try {
            if (empty($provided) === false) {
                $form->populateModel($type);
                $type->save();
            }
            if ($elasticSchemaIds !== null) {
                $existingQuery = $type->getTypeElasticSchemasQuery();
                /** @var TypeElasticSchema $typeElasticSchema */
                foreach ($existingQuery->each() as $typeElasticSchema) {
                    $typeElasticSchema->delete();
                }
                $this->linkElasticSchemas($type, $elasticSchemaIds);
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $updatedTypeQuery = Type::query()->andWhere([
            'id' => $id,
        ])->with([
            'elasticSchemas',
        ]);

        return $this->item($updatedTypeQuery->one());
    }

    /**
     * @param array<int, int>|null $elasticSchemaIds
     */
    private function assertElasticSchemaIds(?array $elasticSchemaIds): void
    {
        if ($elasticSchemaIds !== null) {
            $foundIds = [];
            $foundIdsQuery = ElasticSchema::query()
                ->select('id')
                ->andWhere([
                    'in',
                    'id',
                    $elasticSchemaIds,
                ]);
            foreach ($foundIdsQuery->column() as $foundId) {
                $foundIds[] = (int) $foundId;
            }
            $missingIds = array_diff($elasticSchemaIds, $foundIds);
            if (empty($missingIds) === false) {
                throw new RuntimeException('Unknown elasticSchema ids: ' . implode(', ', $missingIds));
            }
        }
    }

    /**
     * @param array<int, int> $elasticSchemaIds
     */
    private function linkElasticSchemas(Type $type, array $elasticSchemaIds): void
    {
        foreach ($elasticSchemaIds as $elasticSchemaId) {
            $typeElasticSchema = new TypeElasticSchema();
            $typeElasticSchema->setTypeId($type->getId());
            $typeElasticSchema->setElasticSchemaId($elasticSchemaId);
            $typeElasticSchema->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function item(Type $type): array
    {
        $elasticSchemaRefs = [];
        /** @var ElasticSchema $elasticSchema */
        foreach ($type->elasticSchemas as $elasticSchema) {
            $elasticSchemaRefs[] = [
                'id' => $elasticSchema->getId(),
                'name' => $elasticSchema->getName(),
            ];
        }

        return [
            'id' => $type->getId(),
            'name' => $type->getName(),
            'handler' => $type->getHandler(),
            'contentAllowed' => $type->isContentAllowed(),
            'tagAllowed' => $type->isTagAllowed(),
            'elasticSchemas' => $elasticSchemaRefs,
        ];
    }
}
