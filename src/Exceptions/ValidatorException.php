<?php

namespace Fruit\CheckKit\Exceptions;

use Exception;

class ValidatorException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct($name . ' is not a valid validator.');
    }
}
