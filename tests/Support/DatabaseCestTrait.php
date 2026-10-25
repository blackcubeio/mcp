<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Tests\Support;

use Blackcube\Dcore\Models\Bloc;
use Blackcube\Dcore\Models\Content;
use Blackcube\Dcore\Models\GlobalXeo;
use Blackcube\Dcore\Models\Tag;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ConnectionProvider;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Injector\Injector;

/**
 * Trait for Cest classes that need database setup.
 *
 * Lifecycle: drop all tables + migrate the mcp migrations before each test, so
 * every test starts from a clean, freshly-migrated schema.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait DatabaseCestTrait
{
    private const NAMESPACES = [
        'Blackcube\\Dcore\\Migrations',
        'Blackcube\\Mcp\\Migrations',
    ];

    protected ConnectionInterface $db;
    protected Migrator $migrator;
    protected MigrationService $service;

    public function _before(IntegrationTester $I): void
    {
        $this->initializeDatabase();
        $this->dropAllTables();
        $this->migrateUp();

        Content::clearSchemaCache();
        Tag::clearSchemaCache();
        Bloc::clearSchemaCache();
        GlobalXeo::clearSchemaCache();
    }

    private function initializeDatabase(): void
    {
        $helper = new MysqlHelper();
        $this->db = $helper->createConnection();
        ConnectionProvider::set($this->db);

        $containerConfig = ContainerConfig::create()
            ->withDefinitions([
                ConnectionInterface::class => $this->db,
            ]);
        $container = new Container($containerConfig);
        $injector = new Injector($container);

        $this->migrator = new Migrator($this->db, new NullMigrationInformer());
        $this->service = new MigrationService($this->db, $injector, $this->migrator);
        $this->service->setSourceNamespaces(self::NAMESPACES);
    }

    private function dropAllTables(): void
    {
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
        try {
            $tables = $this->db->createCommand('SHOW TABLES')->queryColumn();
            foreach ($tables as $table) {
                $this->db->createCommand('DROP TABLE IF EXISTS `' . $table . '`')->execute();
            }
        } finally {
            $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
        }
    }

    private function migrateUp(): void
    {
        $migrations = $this->service->getNewMigrations();
        foreach ($migrations as $class) {
            $this->migrator->up($this->service->makeMigration($class));
        }
    }
}
