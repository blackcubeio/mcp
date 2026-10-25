<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Helpers;

use RuntimeException;

/**
 * Applies elastic values to a model through its form (elastic scenario).
 *
 * The form is initialised from the model so the elastic schema is known,
 * then validated; a RuntimeException carries the validation messages.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ElasticFormHelper
{
    /**
     * @param class-string $formClass
     * @param array<string, mixed> $elasticData
     */
    public static function apply(string $formClass, object $model, array $elasticData): void
    {
        $elasticForm = new $formClass();
        $elasticForm->initFromModel($model);
        $elasticForm->setScenario('elastic');
        $elasticForm->load($elasticData, '');
        if ($elasticForm->validate() === false) {
            throw new RuntimeException(implode('; ', $elasticForm->getValidationResult()->getErrorMessages()));
        }
        $elasticForm->populateModel($model);
    }
}
