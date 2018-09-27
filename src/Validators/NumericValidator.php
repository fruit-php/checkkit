<?php

namespace Fruit\CheckKit\Validators;

use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Exceptions\InvalidTypeException;
use Fruit\CheckKit\Exceptions\InvalidRuleException;
use Fruit\CheckKit\Exceptions\InvalidFormatException;

/**
 * NumericValidator is a validator for numeric data.
 *
 * Supported rules:
 *
 * * min: numeric. Minimum allowed value. (inclusive)
 * * max: numeric. Maximum allowed value. (inclusive)
 *
 * \code{.php}
 * (new NumericValidator)->validate(1, ['min' => -1.5, 'max' => 3.3]);
 * \endcode
 */
class NumericValidator implements Validator
{
    /**
     * @see CheckKit::Validator
     */
    public function validate($val, array $rule)
    {
        if (! is_numeric($val)) {
            return new InvalidTypeException('numeric');
        }

        if (isset($rule['min']) or isset($rule['max'])) {
            return $this->checkMinMax($val, $rule);
        }

        return null;
    }

    private function checkMinMax($val, array $rule)
    {
        if (isset($rule['min']) and !is_numeric($rule['min'])) {
            throw new InvalidRuleException('min must be numeric value');
        }

        if (isset($rule['max']) and !is_numeric($rule['max'])) {
            throw new InvalidRuleException('max must be numeric value');
        }

        if (isset($rule['min']) and
            isset($rule['max']) and
            $rule['min'] > $rule['max']
        ) {
            throw new InvalidRuleException('min must be less or equal than max');
        }

        if (isset($rule['min']) and $rule['min'] > $val) {
            return new InvalidFormatException;
        }

        if (isset($rule['max']) and $rule['max'] < $val) {
            return new InvalidFormatException;
        }

        return null;
    }
}
