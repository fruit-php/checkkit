<?php

namespace Fruit\CheckKit\Validators;

use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Repo;
use Fruit\CheckKit\Exceptions\InvalidTypeException;
use Fruit\CheckKit\Exceptions\InvalidFormatException;
use Fruit\CheckKit\Exceptions\InvalidRuleException;
use Fruit\CheckKit\Exceptions\InvalidElementException;

/**
 * ArrayValidator validates data is indexed array. The keys of indexed array is
 * always integer and begin from 0.
 *
 * Supported rules:
 *
 * * min_length: integer. minimum length. (inclusive)
 * * max_length: integer. maximum length. (inclusive)
 * * strict: boolean. true if enable strict typing mode, which is default
 * * data: string. validator to check against element data. (optional)
 * * data_rules: array. rules for data validator. (optional)
 *
 * ### Strict typing mode
 *
 * By default, ArrayValidator checks if every key is integer and >= 0. In strict
 * typing mode, it checks following rule in addition.
 *
 * * Lowest key must be 0.
 * * Highest key must be count()-1.
 */
class ArrayValidator implements Validator
{
    /**
     * @see CheckKit::Validator
     */
    public function validate(Repo $repo, $val, array $rule)
    {
        if (!is_array($val)) {
            return new InvalidTypeException('indexed array');
        }

        if (isset($rule['min_length']) or isset($rule['max_length'])) {
            $ret = $this->checkLength($val, $rule);
            if ($ret !== null) {
                return $ret;
            }
        }

        $max = 0;
        // check index typing
        foreach ($val as $k => $v) {
            if (!is_int($k) or $k < 0) {
                return new InvalidTypeException('indexed array');
            }

            if ($max < $k) {
                $max = $k;
            }
        }

        $strict = true;
        if (isset($rule['strict'])) {
            $strict = !!$rule['strict'];
        }

        if ($strict) {
            // strict typing check
            $l = count($val);
            if ($l !== 0 and $l != $max + 1) {
                return new InvalidTypeException('indexed array');
            }
        }

        if (isset($rule['data'])) {
            return $this->checkData($repo, $val, $rule);
        }

        return null;
    }

    private function checkData($repo, $val, $rule)
    {
        $vName = $rule['data'];
        if (!is_string($vName)) {
            throw new InvalidRuleException(
                'data must be a string of pre-registered validator.'
            );
        }
        $validator = $repo->get($vName);

        $vRule = [];
        if (isset($rule['data_rules'])) {
            if (! is_array($rule['data_rules'])) {
                throw new InvalidRuleException(
                    'data rules must be array, or just emit.'
                );
            }
            $vRule = $rule['data_rules'];
        }

        foreach ($val as $k => $v) {
            $ret = $validator->validate($repo, $v, $vRule);
            if ($ret !== null) {
                return new InvalidElementException((string)$k);
            }
        }

        return null;
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
