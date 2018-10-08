<?php

namespace Fruit\CheckKit\Validators;

use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Exceptions\InvalidTypeException;
use Fruit\CheckKit\Exceptions\InvalidRuleException;
use Fruit\CheckKit\Exceptions\InvalidFormatException;

/**
 * FloatValidator is a validator for float data.
 *
 * @see AbstractNumberV
 * \code{.php}
 * (new FloatValidator)->validate($repo, 1, ['min' => -1.5, 'max' => 3.3]);
 * \endcode
 */
class FloatValidator extends AbstractNumberV
{
    /**
     * @see AbstractNumberV::checkType
     */
    protected function checkType($val): string
    {
        $ret = '';
        if (!is_float($val) and !is_int($val)) {
            $ret = 'float';
        }

        return $ret;
    }
}
