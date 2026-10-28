<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\MigrationInterface;
use Yiisoft\Db\Query\Query;

/**
 * Adds to the 'mcp' translation category the keys src/i18n/en/mcp.php holds
 * and the database does not yet, each with its translations from every
 * locale. A base seeded by SeedMcpTranslations before those keys gets them; a
 * base seeded from now on already has them. Not revertible: the keys it adds
 * cannot be told apart later from the seeded ones.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class M000000010002AddMissingMcpTranslations implements MigrationInterface
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
            $knownKeys = [];
            $knownKeysQuery = (new Query($b->getDb()))
                ->select([
                    'message_id',
                ])
                ->from('{{%yii_source_message}}')
                ->andWhere([
                    'category' => self::CATEGORY,
                ]);
            /** @var array{message_id: string} $sourceMessage */
            foreach ($knownKeysQuery->each() as $sourceMessage) {
                $knownKeys[$sourceMessage['message_id']] = true;
            }

            $enMessages = require $enFile;
            $messageMap = [];
            foreach (array_keys($enMessages) as $key) {
                if (isset($knownKeys[$key]) === false) {
                    $b->insert('{{%yii_source_message}}', [
                        'category' => self::CATEGORY,
                        'message_id' => $key,
                    ]);
                    $messageMap[$key] = (int) $b->getDb()->getLastInsertID();
                }
            }

            $translationRows = [];
            foreach (glob($i18nPath . '/*', GLOB_ONLYDIR) as $dir) {
                $locale = basename($dir);
                $file = $i18nPath . '/' . $locale . '/' . self::CATEGORY . '.php';
                if (file_exists($file) === true) {
                    $localeMessages = require $file;
                    foreach ($messageMap as $key => $sourceId) {
                        if (isset($localeMessages[$key]) === true) {
                            $translationRows[] = [
                                $sourceId,
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
}
