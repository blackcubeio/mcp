<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Dboard\Handlers\Keys;

use Blackcube\Dboard\Handlers\Commons\AbstractPanel;
use Blackcube\Mcp\Models\ApiKey;
use Blackcube\Bleet\Enums\DialogType;
use Blackcube\Bleet\Enums\UiColor;

/**
 * Revoke confirmation modal (GET). The confirm form DELETEs to the same path,
 * handled by Keys\Delete. Reuses the dboard commons confirmation content.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class DeleteInit extends AbstractPanel
{
    protected function getType(): DialogType
    {
        return DialogType::Modal;
    }

    protected function getModelClass(): string
    {
        return ApiKey::class;
    }

    protected function getTitle(): string
    {
        return $this->translator->translate('Revoke key', category: 'mcp');
    }

    protected function getContentView(): string
    {
        return 'Commons/_delete-content';
    }

    protected function getMessage(): string
    {
        return $this->translator->translate(
            'Key "{name}" will be revoked. Its token stops working.',
            [
                'name' => $this->getModelName(),
            ],
            'mcp',
        );
    }

    protected function getColor(): UiColor
    {
        return UiColor::Danger;
    }
}
