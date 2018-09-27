<?php

namespace Fruit\CheckKit\Validators;

use Exception;
use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Exceptions\InvalidTypeException;
use Fruit\CheckKit\Exceptions\InvalidRuleException;
use Fruit\CheckKit\Exceptions\InvalidFormatException;

/**
 * StringValidator is a validator for string typing data.
 *
 * Supported rules:
 *
 * * empty: boolean. True if empty string is considered as valid, which is default.
 * * regex: string. Regexp pattern to match (using mb_ereg_match).
 * * regex_modes: string. Third argument of mb_ereg_match.
 * * min_length: int. Minimum length of the string. (counted by mb_strlen)
 * * max_length: int. Maximum length of the string. (counted by mb_strlen)
 *
 * \code{.php}
 * (new StringValidator)->validate('123true', [
 *     'empty' => false, // cannot be an empty string
 *     'regex' => '[0-9]+(true|false)', // matching with regex
 *     'min_length' => 10,
 *     'max_length' => 20, // between 10-20 (inclusive) characters
 * ]);
 * \endcode
 */
class StringValidator implements Validator
{
    /**
     * @see CheckKit::Validator
     */
    public function validate($val, array $rule)
    {
        if (! is_string($val)) {
            return new InvalidTypeException('string');
        }

        if (isset($rule['empty']) and !$rule['empty'] and $val === '') {
            return new InvalidFormatException;
        }

        $ret = $this->checkLength($val, $rule, null);
        $ret = $this->checkRegex($val, $rule, $ret);

        return $ret;
    }

    private function checkRegex($val, array $rule, Exception $e = null)
    {
        if ($e !== null) {
            return $e;
        }
        if (!isset($rule['regex'])) {
            return null;
        }

        $opts = '';
        if (isset($rule['regex_modes'])) {
            $opts .= $rule['regex_modes'];
        }

        if (!mb_ereg_match($rule['regex'], $val, $opts)) {
            return new InvalidFormatException;
        }

        return null;
    }

    private function checkLength($val, array $rule, Exception $e = null)
    {
        if ($e !== null) {
            return $e;
        }

        if (isset($rule['max_length'])) {
            $r = $rule['max_length'];
            if (!is_int($r) or $r < 1) {
                throw new InvalidRuleException(
                    'max_length must be an integer which >= 1'
                );
            }

            if (mb_strlen($val) > $r) {
                return new InvalidFormatException;
            }
        }

        if (isset($rule['min_length'])) {
            $r = $rule['min_length'];
            if (!is_int($r) or $r < 1) {
                throw new InvalidRuleException(
                    'max_length must be an integer which >= 1'
                );
            }
            if (isset($rule['max_length']) and $r > $rule['max_length']) {
                throw new InvalidRuleException(
                    'max_length must >= min_length'
                );
            }

            if (mb_strlen($val) < $r) {
                return new InvalidFormatException;
            }
        }

        return null;
    }
}
