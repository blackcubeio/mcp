<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Mcp\Models\ApiKey;
use Blackcube\Bleet\Bleet;
use Blackcube\Dboard\Widgets\Widgets;
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
        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <?php echo Widgets::pageHeading($translator->translate('MCP keys', category: 'mcp'))
                ->addAction(Bleet::a($translator->translate('Create key', category: 'mcp'), $urlGenerator->generate('mcp.keys.create'))
                    ->button()
                    ->icon('plus')
                    ->accent()
                    ->render())
                ->render();
            ?>
            <div class="dboard-surface p-4">
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
