<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Bleet\Bleet;
use Blackcube\Dboard\Widgets\Widgets;
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
        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <?php echo Widgets::pageHeading($translator->translate('Key token', category: 'mcp'))
                ->backTo($urlGenerator->generate('mcp.keys'), $translator->translate('MCP keys', category: 'mcp'))
                ->render();
            ?>
            <div class="dboard-surface p-6 space-y-4">
                <p class="text-warning-700 font-medium">
                    <?php echo Html::encode($translator->translate('Copy this token now — it is shown only once and cannot be recovered.', category: 'mcp')); ?>
                </p>
                <pre class="bg-secondary-100 rounded-md p-3 text-sm break-all"><?php echo Html::encode($token); ?></pre>
                <div class="flex justify-end">
                    <?php echo Bleet::a($translator->translate('Back to keys', category: 'mcp'), $urlGenerator->generate('mcp.keys'))
                        ->button()
                        ->icon('arrow-left')
                        ->accent()
                        ->render();
                    ?>
                </div>
            </div>
        </main>
