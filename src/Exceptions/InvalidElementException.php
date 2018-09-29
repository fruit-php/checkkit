<?php

namespace Fruit\CheckKit\Exceptions;

class InvalidElementException extends \Exception
{
    public $key;
    public function __construct(string $key, string $cause = ': format is incorrect')
    {
        parent::__construct($key . $cause);
        $this->key = $key;
    }
}
