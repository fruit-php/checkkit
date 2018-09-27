<?php

namespace Fruit\CheckKit\Exceptions;

use Exception;

class InvalidFormatException extends Exception
{
    public function __construct()
    {
        parent::__construct('data format is invalid.');
    }
}
