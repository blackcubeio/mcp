<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Mcp\Models\ApiKey;
use Blackcube\Mcp\Dboard\Forms\KeyForm;
use Blackcube\Bleet\Bleet;
use Blackcube\Dboard\Widgets\Widgets;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var Yiisoft\View\WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var KeyForm $keyForm
 * @var ApiKey $apiKey
 * @var array<string, array<string, string>> $groups
 * @var string[] $errors
 * @var string|null $csrf
 */


?>
        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <?php echo Widgets::pageHeading((string) $apiKey->getName())
                ->subtitle($translator->translate('MCP key', category: 'mcp') . ' #' . $apiKey->getId())
                ->backTo($urlGenerator->generate('mcp.keys'), $translator->translate('MCP keys', category: 'mcp'))
                ->render();
            ?>
            <?php echo Html::form()
                ->post($urlGenerator->generate('mcp.keys.edit', [
                    'id' => $apiKey->getId(),
                ]))
                ->csrf($csrf)
                ->noValidate()
                ->open();
            ?>
                <div class="dboard-surface p-6 space-y-6">
                    <?php if (count($errors) > 0): ?>
                        <?php echo Bleet::alert()->content(implode(' — ', $errors))->danger()->render(); ?>
                    <?php endif; ?>

                    <?php echo Bleet::input($keyForm, 'name')->render(); ?>

                    <?php echo Bleet::input($keyForm, 'dateExpire')->date()->render(); ?>

                    <div>
                        <span class="block text-sm font-medium text-secondary-700 mb-2"><?php echo Html::encode($translator->translate('Scopes', category: 'mcp')); ?></span>
                        <div class="space-y-4">
                            <?php foreach ($groups as $role => $items): ?>
                                <div class="border border-secondary-200 rounded-lg overflow-hidden">
                                    <div class="bg-secondary-50 px-4 py-3 flex items-center gap-2">
                                        <?php echo Bleet::svg()->outline('shield-check')->addClass('size-5', 'text-secondary-600')->render(); ?>
                                        <span class="font-medium text-secondary-900"><?php echo Html::encode($role); ?></span>
                                    </div>
                                    <?php echo Bleet::toggleList($keyForm, 'scopes')
                                        ->items($items)
                                        ->hideLabel()
                                        ->primary()
                                        ->render();
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="dboard-actions">
                    <?php echo Bleet::a($translator->translate('Cancel', category: 'mcp'), $urlGenerator->generate('mcp.keys'))
                        ->button()
                        ->icon('x-mark')
                        ->ghost()
                        ->secondary()
                        ->render();
                    ?>
                    <?php echo Bleet::submitButton($translator->translate('Save', category: 'mcp'))->icon('check')->accent()->render(); ?>
                </div>
            <?php echo Html::form()->close(); ?>
        </main>
