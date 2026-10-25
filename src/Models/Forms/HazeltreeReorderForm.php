<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Models\Forms;

use Blackcube\Dboard\Enums\TreePosition;
use Blackcube\BridgeFormModel\BridgeFormModel;
use Blackcube\BridgeFormModel\Attributes\Bridge;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Required;

/**
 * Hazeltree reorder form model (ported from dboard, UI labels stripped).
 * Validates sourceId, targetId and position parameters.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class HazeltreeReorderForm extends BridgeFormModel
{
    private string $sourceId = '';
    private string $targetId = '';
    private string $position = '';

    #[Bridge]
    public function getSourceId(): string
    {
        return $this->sourceId;
    }
    #[Bridge]
    public function setSourceId(string $sourceId): void
    {
        $this->sourceId = $sourceId;
    }
    #[Bridge]
    public function getTargetId(): string
    {
        return $this->targetId;
    }
    #[Bridge]
    public function setTargetId(string $targetId): void
    {
        $this->targetId = $targetId;
    }
    #[Bridge]
    public function getPosition(): string
    {
        return $this->position;
    }
    #[Bridge]
    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'sourceId' => [
                new Required(),
            ],
            'targetId' => [
                new Required(),
            ],
            'position' => [
                new Required(),
                new In([
                    TreePosition::Before->value,
                    TreePosition::Into->value,
                    TreePosition::After->value,
                ]),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return [
            'default' => [
                'sourceId',
                'targetId',
                'position',
            ],
        ];
    }
}
