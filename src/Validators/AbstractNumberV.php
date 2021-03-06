<?php

namespace Fruit\CheckKit\Validators;

use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Repo;
use Fruit\CheckKit\Exceptions\InvalidTypeException;
use Fruit\CheckKit\Exceptions\InvalidRuleException;
use Fruit\CheckKit\Exceptions\InvalidFormatException;

/**
 * AbstractNumberV is shared facilities among numeric, integer and float.
 *
 * Supported rules:
 *
 * * inc: boolean. true min/max is inclusive, which is default.
 * * min: Minimum allowed value.
 * * max: Maximum allowed value.
 */
abstract class AbstractNumberV implements Validator
{
    /**
     * Implement type checking here. Return string of correct type if invalid.
     */
    abstract protected function checkType($val): string;

    /**
     * @see CheckKit::Validator
     */
    public function validate(Repo $repo, $val, array $rule)
    {
        $t = $this->checkType($val);
        if ($t !== '') {
            return new InvalidTypeException($t);
        }

        if (isset($rule['min']) or isset($rule['max'])) {
            return $this->checkMinMax($val, $rule);
        }

        return null;
    }

    private function checkMinMax($val, array $rule)
    {
        $inc = true;
        if (isset($rule['inc'])) {
            $inc = !!$rule['inc'];
        }
        if (isset($rule['min']) and ($t = $this->checkType($rule['min'])) !== '') {
            throw new InvalidRuleException('min must be ' . $t . ' value');
        }

        if (isset($rule['max']) and ($t = $this->checkType($rule['max'])) !== '') {
            throw new InvalidRuleException('max must be ' . $t . ' value');
        }

        if (isset($rule['min']) and
            isset($rule['max']) and
            $rule['min'] > $rule['max']
        ) {
            throw new InvalidRuleException('min must be less or equal than max');
        }

        if (isset($rule['min'])) {
            if ($rule['min'] > $val) {
                return new InvalidFormatException;
            }
            if (!$inc and $rule['min'] == $val) {
                return new InvalidFormatException;
            }
        }

        if (isset($rule['max'])) {
            if ($rule['max'] < $val) {
                return new InvalidFormatException;
            }
            if (!$inc and $rule['max'] == $val) {
                return new InvalidFormatException;
            }
        }

        return null;
    }
}
