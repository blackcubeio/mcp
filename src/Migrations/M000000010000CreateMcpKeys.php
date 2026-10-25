<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * Migration to create the mcpKeys table
 *
 * Standalone MCP keys, decoupled from dboard. The token is never stored; only
 * its SHA-256 hash is kept in the hash column. Each key carries its own scopes.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class M000000010000CreateMcpKeys implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('{{%mcpKeys}}', [
            'id' => ColumnBuilder::bigPrimaryKey(),
            'name' => ColumnBuilder::string()->notNull(),
            'hash' => ColumnBuilder::string(64)->notNull()->unique(),
            'scopes' => ColumnBuilder::json(),
            'dateExpire' => ColumnBuilder::datetime()->notNull(),
            'revoked' => ColumnBuilder::boolean()->notNull()->defaultValue(false),
            'dateLastUse' => ColumnBuilder::datetime(),
            'dateCreate' => ColumnBuilder::datetime()->notNull(),
            'dateUpdate' => ColumnBuilder::datetime(),
        ]);

        $b->createIndex('{{%mcpKeys}}', 'mcpKeys__dateExpire_idx', [
            'dateExpire',
        ]);
        $b->createIndex('{{%mcpKeys}}', 'mcpKeys__revoked_idx', [
            'revoked',
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('{{%mcpKeys}}');
    }
}
