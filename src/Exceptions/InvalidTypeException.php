<?php

namespace Fruit\CheckKit\Exceptions;

use Exception;

class InvalidTypeException extends Exception
{
    public function __construct(string $t)
    {
        parent::__construct('data is not a ' . $t);
    }
}
