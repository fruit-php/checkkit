<?php

namespace Fruit\CheckKit\Validators;

use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Exceptions\InvalidTypeException;

/**
 * BoolValidator validates data is bool.
 *
 * It does not support any custom rule, just type checking.
 */
class BoolValidator implements Validator
{
    /**
     * @see CheckKit::Validator
     */
    public function validate($val, array $rule)
    {
        if (!is_bool($val)) {
            return new InvalidTypeException('bool');
        }

        return null;
    }
}
