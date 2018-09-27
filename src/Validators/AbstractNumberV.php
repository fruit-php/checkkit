<?php

namespace Fruit\CheckKit\Validators;

use Fruit\CheckKit\Validator;
use Fruit\CheckKit\Exceptions\InvalidTypeException;
use Fruit\CheckKit\Exceptions\InvalidRuleException;
use Fruit\CheckKit\Exceptions\InvalidFormatException;

/**
 * AbstractNumberV is shared facilities among numeric, integer and float.
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
    public function validate($val, array $rule)
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

        if (isset($rule['min']) and $rule['min'] > $val) {
            return new InvalidFormatException;
        }

        if (isset($rule['max']) and $rule['max'] < $val) {
            return new InvalidFormatException;
        }

        return null;
    }
}
