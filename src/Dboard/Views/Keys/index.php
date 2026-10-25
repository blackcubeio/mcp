<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Mcp\Models\ApiKey;
use Blackcube\Bleet\Bleet;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var Yiisoft\View\WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var ApiKey[] $keys
 * @var int[] $expiredIds
 */


?>
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="bg-white rounded-lg shadow-sm border border-secondary-200 p-4 mb-6 flex justify-end">
                <?php echo Bleet::a($translator->translate('Create key', category: 'mcp'), $urlGenerator->generate('mcp.keys.create'))
                    ->button()
                    ->icon('plus')
                    ->primary()
                    ->render();
                ?>
            </div>

            <?php echo Bleet::cardHeader()
                ->icon('key')
                ->title($translator->translate('MCP keys', category: 'mcp'))
                ->primary();
            ?>

            <div class="bg-white rounded-b-lg shadow-lg p-4">
                <bleet-ajaxify id="mcp-keys-list">
                <?php echo $this->render('Keys/_list', [
                    'urlGenerator' => $urlGenerator,
                    'translator' => $translator,
                    'keys' => $keys,
                    'expiredIds' => $expiredIds,
                ]); ?>
                </bleet-ajaxify>
            </div>
        </main>
