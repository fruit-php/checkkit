<?php

namespace Fruit\CheckKit\Validators;

use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Repo;
use Fruit\CheckKit\Exceptions\InvalidTypeException;
use Fruit\CheckKit\Exceptions\InvalidFormatException;
use Fruit\CheckKit\Exceptions\InvalidRuleException;
use Fruit\CheckKit\Exceptions\InvalidElementException;

/**
 * DictValidator validates dictionary, which is an associative array with string
 * keys.
 *
 * WARNING: Due to limitation of PHP, it is not possible to tell the difference
 * `[1]`, `[0 => 1]` and `['0' => 1]`, thus indexed array is considered as
 * dictionary with serial numeric string key.
 *
 * Supported rules:
 *
 * * strict: boolean. True to enable strict mode. Default false.
 * * elements: array of rules to check elements. we'll describe it in later section.
 * * min_length: integer. minimum length. (inclusive)
 * * max_length: integer. maximum length. (inclusive)
 *
 * ### Strict mode
 *
 * By default (strict == false), DictValidator does not validate unknown elements.
 * In strict mode, any unknown keys are considered invalid.
 *
 * ### Element checking
 *
 * Element rules looks like this:
 *
 * \code{.php}
 * [
 *     "key1" => [ // named key
 *         "type" => "int", // IntValidator, omit to bypass content check
 *         "required" => true, // default to false
 *         "rules" => []
 *     ],
 *     "key2" => [
 *         "type" => "array", // ArrayValidator
 *         "required" => false,
 *         "rules" => ["min_length" => 3]
 *     ],
 *     "*" => [ // catch-all rule
 *         "regex" => "key[1-9][0-9]*$", // match with mb_ereg_match
 *         "regex_mode" => "",
 *         "type" => "int",
 *         "required" => false, // always false, even you set it to true
 *         "rules" => []
 *     ]
 * ]
 * \endcode
 *
 * This validates `["key1" => 1]` and `["key1" => 1, "key123" => 10]`, but failes at
 *
 * * `["key5" => 1]` (no required key)
 * * `["key1" => 1, "key2" => 3]` (key2 must be array)
 * * `["key1" => 1, "key2" => [1, 2]]` (count(key2) must >= 3)
 *
 * The catch-all rule matches all unknown keys, which works same as strict mode.
 *
 * If you do not need to validate content of element, just omit "type".
 */
class DictValidator implements Validator
{
    /**
     * @see Validator
     */
    public function validate(Repo $repo, $val, array $rule)
    {
        if (!is_array($val)) {
            return new InvalidTypeException('dictionary');
        }

        $strict = false;
        if (isset($rule['strict'])) {
            $strict = !!$rule['strict'];
        }

        if (isset($rule['min_length']) or isset($rule['max_length'])) {
            $ret = $this->checkLength($val, $rule);
            if ($ret !== null) {
                return $ret;
            }
        }

        if (!isset($rule['elements'])) {
            foreach ($val as $k => $v) {
                if (!is_string($k) and !is_int($k)) {
                    return new InvalidElementException(var_export($k, true), ' (key) is not a string');
                }
            }

            return null;
        }

        if (!is_array($rule['elements'])) {
            throw new InvalidRuleException(
                'elements must be an array, or just emits it'
            );
        }

        // check required keys
        foreach ($rule['elements'] as $k => $r) {
            if (!is_array($r)) {
                throw new InvalidRuleException(
                    'element rule must be array'
                );
            }

            if (!isset($r['required']) or $k === '*') {
                continue;
            }

            if (!!$r['required'] and !isset($val[$k])) {
                return new InvalidElementException($k, ' not found, but required.');
            }
        }

        foreach ($val as $k => $v) {
            if (!is_string($k) and !is_int($k)) {
                return new InvalidElementException(var_export($k, true), ' (key) is not a string');
            }

            $ret = $this->checkData(
                $repo,
                $strict,
                $k,
                $v,
                $rule['elements']
            );
            if ($ret !== null) {
                return $ret;
            }
        }

        return null;
    }

    private function checkData($repo, $strict, $k, $v, $rule)
    {
        if (!isset($rule[$k])) {
            // no known rules
            if (isset($rule['*'])) {
                return $this->checkCatchAll($repo, $k, $v, $rule['*']);
            }

            if ($strict) {
                return new InvalidElementException($k, ' is not valid key');
            }

            return null;
        }

        return $this->checkItem($repo, $k, $v, $rule[$k]);
    }

    private function checkCatchAll($repo, $k, $v, $r)
    {
        if (!is_string($k) and !is_int($k)) {
            return new InvalidElementException(var_export($k, true), ' (key) is not a string');
        }

        // check key format
        if (isset($r['regex'])) {
            if (!is_string($r['regex'])) {
                throw new InvalidRuleException(
                    'regex must be string'
                );
            }
            $regex = $r['regex'];
            $mode = '';

            if (isset($r['regex_mode'])) {
                if (!is_string($r['regex_mode'])) {
                    throw new InvalidRuleException(
                        'regex_mode must be string'
                    );
                }
                $mode = $r['regex_mode'];
            }

            if (!mb_ereg_match($regex, $k, $mode)) {
                return new InvalidElementException($k, ' is not a catch-all key');
            }
        }

        return $this->checkItem($repo, $k, $v, $r);
    }

    private function checkItem($repo, $k, $v, $r)
    {
        if (!isset($r['type'])) {
            return null;
        }

        $validator = $repo->get($r['type']);
        $vRule = [];
        if (isset($r['rules'])) {
            if (!is_array($r['rules'])) {
                throw new InvalidRuleException(
                    'element validator rule must be array'
                );
            }

            $vRule = $r['rules'];
        }

        $ret = $validator->validate($repo, $v, $vRule);
        if ($ret !== null) {
            $ret = new InvalidElementException($k, ': ' . $ret->getMessage());
        }

        return $ret;
    }

    private function checkLength($val, array $rule)
    {
        $l = count($val);

        if (isset($rule['min_length'])) {
            if (!is_int($rule['min_length'])) {
                throw new InvalidRuleException(
                    'min_length must be integer'
                );
            }
            if ($rule['min_length'] < 0) {
                throw new InvalidRuleException(
                    'min_length must >= 0'
                );
            }

            if ($l < $rule['min_length']) {
                return new InvalidFormatException;
            }
        }

        if (isset($rule['max_length'])) {
            if (!is_int($rule['max_length'])) {
                throw new InvalidRuleException(
                    'max_length must be integer'
                );
            }
            if ($rule['max_length'] < 1) {
                throw new InvalidRuleException(
                    'max_length must >= 1'
                );
            }

            if (isset($rule['min_length']) and
                $rule['min_length'] > $rule['max_length']
            ) {
                throw new InvalidRuleException(
                    'max_length must >= min_length'
                );
            }

            if ($l > $rule['max_length']) {
                return new InvalidFormatException;
            }
        }

        return null;
    }
}
