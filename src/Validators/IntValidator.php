<?php

namespace Fruit\CheckKit\Validators;

use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Exceptions\InvalidTypeException;
use Fruit\CheckKit\Exceptions\InvalidRuleException;
use Fruit\CheckKit\Exceptions\InvalidFormatException;

/**
 * IntValidator is a validator for integer data.
 *
 * @see AbstractNumberV
 * \code{.php}
 * (new IntValidator)->validate($repo, 1, ['min' => -1, 'max' => 3]);
 * \endcode
 */
class IntValidator extends AbstractNumberV
{
    /**
     * @see AbstractNumberV::checkType
     */
    protected function checkType($val): string
    {
        $ret = '';
        if (!is_int($val)) {
            $ret = 'int';
        }

        return $ret;
    }
}
