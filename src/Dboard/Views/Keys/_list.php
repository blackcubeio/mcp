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

if (empty($keys) === true):
    echo Bleet::emptyState()
        ->icon('key')
        ->title($translator->translate('No key', category: 'mcp'))
        ->description($translator->translate('Start by creating a new MCP key.', category: 'mcp'))
        ->button($translator->translate('New key', category: 'mcp'), $urlGenerator->generate('mcp.keys.create'))
        ->primary();
else:
    $dl = Bleet::dl()->cols(4);
    $editPopover = Bleet::popover($translator->translate('Edit', category: 'mcp'))->id('list-popover-edit');
    $rotatePopover = Bleet::popover($translator->translate('Rotate', category: 'mcp'))->id('list-popover-rotate');
    $revokePopover = Bleet::popover($translator->translate('Revoke', category: 'mcp'))->id('list-popover-revoke');
    foreach ($keys as $key):
        /** @var ApiKey $key */
        $editUrl = $urlGenerator->generate('mcp.keys.edit', [
            'id' => $key->getId(),
        ]);
        $rotateUrl = $urlGenerator->generate('mcp.keys.rotate', [
            'id' => $key->getId(),
        ]);
        $revokeUrl = $urlGenerator->generate('mcp.keys.delete', [
            'id' => $key->getId(),
        ]);

        $dateExpire = $key->getDateExpire();
        if ($key->isRevoked() === true):
            $statusBadge = Bleet::badge($translator->translate('Revoked', category: 'mcp'))->danger()->render();
        elseif (in_array($key->getId(), $expiredIds, true) === true):
            $statusBadge = Bleet::badge($translator->translate('Expired', category: 'mcp'))->warning()->render();
        else:
            $statusBadge = Bleet::badge($translator->translate('Active', category: 'mcp'))->success()->render();
        endif;

        $editButton = Bleet::a()->url($editUrl)->icon('pencil')->info()->outline()->xs()
            ->addAttributes($editPopover->trigger())
            ->button();
        $rotateButton = Bleet::button()->icon('arrow-path')->secondary()->outline()->xs()
            ->addAttributes($rotatePopover->trigger())
            ->addAttributes(Bleet::modal()->trigger($rotateUrl));
        $revokeButton = Bleet::button()->icon('trash')->danger()->outline()->xs()
            ->addAttributes($revokePopover->trigger())
            ->addAttributes(Bleet::modal()->trigger($revokeUrl));

        $buttonGroup = Bleet::buttonGroup()
            ->addButton($editButton)
            ->addButton($rotateButton)
            ->addButton($revokeButton);

        $dl = $dl
            ->addItem(Bleet::termItem($translator->translate('Name', category: 'mcp'))->detail($key->getName()))
            ->addItem(Bleet::termItem($translator->translate('Expires', category: 'mcp'))->detail($dateExpire?->format('Y-m-d') ?? '—'))
            ->addItem(Bleet::termItem($translator->translate('Status', category: 'mcp'))->addDetail(Bleet::detailItem($statusBadge)->encode(false)))
            ->addItem(Bleet::termItem($translator->translate('Actions', category: 'mcp'))->addDetail(Bleet::detailItem($buttonGroup->render())->encode(false)));
    endforeach;

    echo $dl->primary()->render();
    echo $editPopover;
    echo $rotatePopover;
    echo $revokePopover;
endif;
