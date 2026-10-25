<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Models\Forms;

use Blackcube\BridgeFormModel\Attributes\Bridge;
use Yiisoft\Validator\Rule\Date\Date;

/**
 * Content form model: the shared element form plus the publication window.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ContentForm extends AbstractElementForm
{
    protected ?string $dateStart = null;
    protected ?string $dateEnd = null;

    #[Bridge(format: 'Y-m-d')]
    public function setDateStart(?string $dateStart): void
    {
        $this->dateStart = $dateStart;
    }
    #[Bridge(format: 'Y-m-d')]
    public function getDateStart(): ?string
    {
        return $this->dateStart;
    }
    #[Bridge(format: 'Y-m-d')]
    public function setDateEnd(?string $dateEnd): void
    {
        $this->dateEnd = $dateEnd;
    }
    #[Bridge(format: 'Y-m-d')]
    public function getDateEnd(): ?string
    {
        return $this->dateEnd;
    }

    /**
     * Content dates are whole days: the start opens at 00:00:01, the end closes at 23:59:59.
     */
    public function populateModel(object $model): void
    {
        parent::populateModel($model);
        $activeFields = $this->getActiveFields();
        if (in_array('dateStart', $activeFields, true) === true && $model->getDateStart() !== null) {
            $model->setDateStart($model->getDateStart()->setTime(0, 0, 1));
        }
        if (in_array('dateEnd', $activeFields, true) === true && $model->getDateEnd() !== null) {
            $model->setDateEnd($model->getDateEnd()->setTime(23, 59, 59));
        }
    }

    protected function extraFields(): array
    {
        return [
            'dateStart',
            'dateEnd',
        ];
    }

    protected function extraRules(): array
    {
        return [
            'dateStart' => [
                new Date(format: 'yyyy-MM-dd', skipOnEmpty: true),
            ],
            'dateEnd' => [
                new Date(format: 'yyyy-MM-dd', skipOnEmpty: true),
            ],
        ];
    }
}
