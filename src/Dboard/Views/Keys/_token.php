<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Bleet\Bleet;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var Yiisoft\View\WebView $this
 * @var TranslatorInterface $translator
 * @var string $token
 */


?>
<div class="p-6 space-y-4">
    <p class="text-warning-700 font-medium">
        <?php echo Html::encode($translator->translate('Copy this token now — it is shown only once and cannot be recovered.', category: 'mcp')); ?>
    </p>
    <pre class="bg-secondary-100 rounded-md p-3 text-sm break-all"><?php echo Html::encode($token); ?></pre>
    <div class="flex justify-end gap-4 pt-4 border-t border-secondary-200">
        <?php echo Bleet::button($translator->translate('Close', category: 'mcp'))
            ->secondary()
            ->fieldData([
                'modal' => 'close',
            ]); ?>
    </div>
</div>
