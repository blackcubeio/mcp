<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dcore\Models\ElasticSchema;
use Blackcube\Dcore\Models\GlobalXeo;
use Blackcube\Dcore\Models\Host;
use Blackcube\Dcore\Services\FileService;
use Blackcube\Mcp\Helpers\ElasticFormHelper;
use Blackcube\Mcp\Models\Forms\GlobalXeoForm;
use RuntimeException;
use Throwable;

/**
 * Read and write logic for the GlobalXeo entries: the site-wide SEO/GEO data
 * of a host, one entry per (host, kind). The four kinds mirror dboard
 * (Organization, WebSite, Robots, Sitemap), each validated by its builtin
 * xeo schema. set() auto-creates the entry like dboard does; the entries are
 * cheap to rebuild, hence a delete verb. Returns plain data; raises a
 * RuntimeException with a clear message on a business error.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class GlobalXeoService
{
    /**
     * @var array<string, string> Kind => builtin xeo schema name validating its data
     */
    private const KINDS = [
        'Organization' => 'Organization',
        'WebSite' => 'WebSite',
        'Robots' => 'RawData',
        'Sitemap' => 'RawData',
    ];

    /**
     * @var array<string, string> Kind => default entry name (same as dboard)
     */
    private const DEFAULT_NAMES = [
        'Organization' => 'Organisation',
        'WebSite' => 'Site Web',
        'Robots' => 'robots.txt',
        'Sitemap' => 'Sitemap additionnel',
    ];

    public function __construct(
        private readonly FileService $fileService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function list(?int $hostId): array
    {
        $hostsQuery = Host::query()->orderBy([
            'id' => SORT_ASC,
        ]);
        if ($hostId !== null) {
            $hostsQuery->andWhere([
                'id' => $hostId,
            ]);
        }

        $globalXeosQuery = GlobalXeo::query()
            ->andWhere([
                'in',
                'kind',
                array_keys(self::KINDS),
            ])
            ->orderBy([
                'id' => SORT_ASC,
            ]);
        if ($hostId !== null) {
            $globalXeosQuery->andWhere([
                'hostId' => $hostId,
            ]);
        }
        $hostGlobalXeos = [];
        /** @var GlobalXeo $globalXeo */
        foreach ($globalXeosQuery->each() as $globalXeo) {
            if (isset($hostGlobalXeos[$globalXeo->getHostId()][$globalXeo->getKind()]) === false) {
                $hostGlobalXeos[$globalXeo->getHostId()][$globalXeo->getKind()] = $globalXeo;
            }
        }

        $items = [];
        /** @var Host $host */
        foreach ($hostsQuery->each() as $host) {
            foreach (array_keys(self::KINDS) as $kind) {
                $globalXeo = $hostGlobalXeos[$host->getId()][$kind] ?? null;
                $entry = [
                    'host' => [
                        'id' => $host->getId(),
                        'name' => $host->getName(),
                    ],
                    'kind' => $kind,
                    'exists' => $globalXeo !== null,
                ];
                if ($globalXeo !== null) {
                    $entry['name'] = $globalXeo->getName();
                    $entry['active'] = $globalXeo->isActive();
                }
                $items[] = $entry;
            }
        }

        return [
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $hostId, string $kind): array
    {
        $this->checkKind($kind);
        $globalXeo = GlobalXeo::query()
            ->andWhere([
                'hostId' => $hostId,
                'kind' => $kind,
            ])
            ->one();
        if ($globalXeo === null) {
            throw new RuntimeException('No ' . $kind . ' globalXeo for host ' . $hostId . ' yet: create it with globalXeo.set');
        }

        return $this->item($globalXeo);
    }

    /**
     * Updates (or auto-creates, like dboard) the (host, kind) entry.
     *
     * @param array<string, mixed>|null $elasticValues
     * @return array<string, mixed>
     */
    public function set(int $hostId, string $kind, ?string $name, ?array $elasticValues, ?bool $active): array
    {
        $this->checkKind($kind);
        $host = Host::query()->andWhere([
            'id' => $hostId,
        ])->one();
        if ($host === null) {
            throw new RuntimeException('Host not found: ' . $hostId);
        }
        $globalXeo = GlobalXeo::query()
            ->andWhere([
                'hostId' => $hostId,
                'kind' => $kind,
            ])
            ->one() ?? $this->create($hostId, $kind);

        $provided = [];
        if ($name !== null) {
            $provided['name'] = $name;
        }
        if ($active !== null) {
            $provided['active'] = $active;
        }
        if (empty($provided) === false) {
            $form = new GlobalXeoForm();
            $form->setScenario('edit');
            $form->setPatchFields(array_keys($provided));
            $form->load($provided, '');
            if ($form->validate() === false) {
                throw new RuntimeException(implode('; ', $form->getValidationResult()->getErrorMessages()));
            }
            $form->populateModel($globalXeo);
        }
        if ($elasticValues !== null) {
            ElasticFormHelper::apply(GlobalXeoForm::class, $globalXeo, $elasticValues);
        }

        $transaction = $globalXeo->db()->beginTransaction();
        try {
            $globalXeo->save();
            $this->fileService->processEntityFiles($globalXeo);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $this->item($globalXeo);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(int $hostId, string $kind): array
    {
        $this->checkKind($kind);
        $globalXeo = GlobalXeo::query()
            ->andWhere([
                'hostId' => $hostId,
                'kind' => $kind,
            ])
            ->one();
        if ($globalXeo === null) {
            throw new RuntimeException('No ' . $kind . ' globalXeo for host ' . $hostId);
        }
        $deleted = $this->item($globalXeo);
        $globalXeo->delete();

        return $deleted;
    }

    private function checkKind(string $kind): void
    {
        if (isset(self::KINDS[$kind]) === false) {
            throw new RuntimeException('Invalid kind "' . $kind . '" (allowed: ' . implode(', ', array_keys(self::KINDS)) . ')');
        }
    }

    /**
     * Auto-creates the entry with the builtin schema of the kind, like dboard.
     */
    private function create(int $hostId, string $kind): GlobalXeo
    {
        $schemaName = self::KINDS[$kind];
        $schema = ElasticSchema::query()
            ->andWhere([
                'name' => $schemaName,
                'kind' => 'xeo',
                'builtin' => true,
            ])
            ->one();
        if ($schema === null) {
            throw new RuntimeException('Builtin schema not found for ' . $schemaName);
        }

        $globalXeo = new GlobalXeo();
        $globalXeo->setHostId($hostId);
        $globalXeo->setKind($kind);
        $globalXeo->setName(self::DEFAULT_NAMES[$kind]);
        $globalXeo->setElasticSchemaId($schema->getId());

        return $globalXeo;
    }

    /**
     * @return array<string, mixed>
     */
    private function item(GlobalXeo $globalXeo): array
    {
        $schema = ElasticSchema::query()
            ->andWhere([
                'id' => $globalXeo->getElasticSchemaId(),
            ])
            ->one();

        return [
            'host' => $this->hostRef($globalXeo->getHostId()),
            'kind' => $globalXeo->getKind(),
            'name' => $globalXeo->getName(),
            'active' => $globalXeo->isActive(),
            'schema' => $schema !== null ? [
                'id' => $schema->getId(),
                'name' => $schema->getName(),
            ] : null,
            'data' => $globalXeo->getElasticValues(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hostRef(int $hostId): array
    {
        $host = Host::query()->andWhere([
            'id' => $hostId,
        ])->one();

        return [
            'id' => $hostId,
            'name' => $host?->getName(),
        ];
    }
}
