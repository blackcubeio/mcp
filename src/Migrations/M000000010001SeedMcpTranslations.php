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

/**
 * Seeds the 'mcp' translation category into the shared i18n tables
 * ({{%yii_source_message}} / {{%yii_message}}, created by dboard). Reads every
 * message from src/i18n/en/mcp.php and its translations from each locale.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class M000000010001SeedMcpTranslations implements RevertibleMigrationInterface
{
    private const CATEGORY = 'mcp';

    /**
     * The i18n tables are owned by dboard's CreateTranslations migration:
     * skips gracefully when they are absent (e.g. a standalone mcp test DB).
     */
    public function up(MigrationBuilder $b): void
    {
        $i18nPath = dirname(__DIR__) . '/i18n';
        $enFile = $i18nPath . '/en/' . self::CATEGORY . '.php';
        if (file_exists($enFile) === true && $b->getDb()->getTableSchema('{{%yii_source_message}}') !== null) {
            $enMessages = require $enFile;
            $keys = array_keys($enMessages);

            foreach ($keys as $key) {
                $b->delete('{{%yii_source_message}}', [
                    'category' => self::CATEGORY,
                    'message_id' => $key,
                ]);
            }

            $messageMap = [];
            foreach ($keys as $key) {
                $b->insert('{{%yii_source_message}}', [
                    'category' => self::CATEGORY,
                    'message_id' => $key,
                ]);
                $messageMap[$key] = (int) $b->getDb()->getLastInsertID();
            }

            $translationRows = [];
            foreach (glob($i18nPath . '/*', GLOB_ONLYDIR) as $dir) {
                $locale = basename($dir);
                $file = $i18nPath . '/' . $locale . '/' . self::CATEGORY . '.php';
                if (file_exists($file) === true) {
                    $localeMessages = require $file;
                    foreach ($keys as $key) {
                        if (isset($messageMap[$key]) === true && isset($localeMessages[$key]) === true) {
                            $translationRows[] = [
                                $messageMap[$key],
                                $locale,
                                $localeMessages[$key],
                            ];
                        }
                    }
                }
            }

            if (empty($translationRows) === false) {
                $b->insertBatch('{{%yii_message}}', $translationRows, [
                    'id',
                    'locale',
                    'translation',
                ]);
            }
        }
    }

    public function down(MigrationBuilder $b): void
    {
        $b->delete('{{%yii_source_message}}', [
            'category' => self::CATEGORY,
        ]);
    }
}
