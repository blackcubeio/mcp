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
 * Rotate confirmation modal (GET). The confirm form POSTs to the same path,
 * handled by Keys\Rotate. Reuses the dboard commons POST confirmation content.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class RotateInit extends AbstractPanel
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
        return $this->translator->translate('Rotate key', category: 'mcp');
    }

    protected function getContentView(): string
    {
        return 'Commons/_toggle-content';
    }

    protected function getMessage(): string
    {
        return $this->translator->translate(
            'A new token will be issued for "{name}" and its expiration reset. The current token stops working.',
            [
                'name' => $this->getModelName(),
            ],
            'mcp',
        );
    }

    protected function getColor(): UiColor
    {
        return UiColor::Warning;
    }
}
