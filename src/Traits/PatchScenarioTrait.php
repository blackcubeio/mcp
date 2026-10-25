<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Traits;

/**
 * PATCH semantics for ported forms: validation and population are restricted to
 * the fields actually provided in the input, within the bounds of the current
 * scenario. A field absent from the input is neither validated nor written.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait PatchScenarioTrait
{
    private array $patchFields = [];
    private bool $patchMode = false;

    public function setPatchFields(array $fields): static
    {
        $this->patchFields = $fields;
        $this->patchMode = true;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getActiveFields(): array
    {
        if ($this->patchMode === true) {
            $allowed = $this->scenarios()[$this->getScenario()] ?? [];
            $activeFields = array_values(array_intersect($this->patchFields, $allowed));
        } else {
            $activeFields = parent::getActiveFields();
        }
        return $activeFields;
    }
}
