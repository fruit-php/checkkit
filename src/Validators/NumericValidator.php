<?php

namespace Fruit\CheckKit\Validators;

use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Exceptions\InvalidTypeException;
use Fruit\CheckKit\Exceptions\InvalidRuleException;
use Fruit\CheckKit\Exceptions\InvalidFormatException;

/**
 * NumericValidator is a validator for numeric data.
 *
 * @see AbstractNumberV
 * \code{.php}
 * (new NumericValidator)->validate(1, ['min' => -1.5, 'max' => 3.3]);
 * \endcode
 */
class NumericValidator extends AbstractNumberV
{
    /**
     * @see AbstractNumberV::checkType
     */
    protected function checkType($val): string
    {
        $ret = '';
        if (!is_numeric($val)) {
            $ret = 'numeric';
        }

        return $ret;
    }
}
