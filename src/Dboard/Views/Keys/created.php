<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Bleet\Bleet;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var Yiisoft\View\WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var string $token
 */


?>
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <?php echo Bleet::cardHeader()
                ->icon('key')
                ->title($translator->translate('Key token', category: 'mcp'))
                ->primary();
            ?>

            <div class="bg-white rounded-b-lg shadow-lg p-4 space-y-4">
                <p class="text-warning-700 font-medium">
                    <?php echo Html::encode($translator->translate('Copy this token now — it is shown only once and cannot be recovered.', category: 'mcp')); ?>
                </p>
                <pre class="bg-secondary-100 rounded-md p-3 text-sm break-all"><?php echo Html::encode($token); ?></pre>
                <div class="flex justify-end">
                    <?php echo Bleet::a($translator->translate('Back to keys', category: 'mcp'), $urlGenerator->generate('mcp.keys'))
                        ->button()
                        ->icon('arrow-left')
                        ->primary()
                        ->render();
                    ?>
                </div>
            </div>
        </main>
