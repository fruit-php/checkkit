<?php

namespace Fruit\CheckKit;

use Fruit\CheckKit\Exceptions\RepoException;
use ReflectionClass;

/**
 * Repo is the main API entry of CheckKit.
 *
 * It is designed to support lazy-load without compilation. See Repo::register.
 */
class Repo
{
    private $cls2obj = [];
    private $alias2cls = [];

    /**
     * Let Repo know about an validator.
     *
     * Supported input:
     *
     * 1. An instance of Validator.
     * 2. A string represent of full class name of validator.
     * 3. A callable. It should return an instance of validator, but not verified
     *    here. You will encounter a typing error when calling Repo::get.
     *
     * For previously registered alias or unsupported input, it throws an
     * Exceptions::RepoException.
     *
     * @throws Exceptions::RepoException
     * @param $alias string
     * @param $validator string|Validator|callable
     */
    public function register(string $alias, string $className)
    {
        if (isset($this->alias2cls[$alias])) {
            throw new RepoException($alias . ' is already registered');
        }

        if (!isset($this->cls2obj[$className]) and class_exists($className)) {
            $ref = new ReflectionClass($className);
            if (! $ref->isSubClassOf('Fruit\CheckKit\Validator')) {
                throw new RepoException(
                    $className . ' is not a Validator.'
                );
            }
            $this->cls2obj[$className] = $className;
        }

        $this->alias2cls[$alias] = $className;
        return;
    }

    /**
     * Get the validator according to previously registered info.
     *
     * It throws Exceptions::RepoException if not found and trigger typing error
     * if provided callable does not return Validator.
     *
     * @throws Exceptions::RepoException
     * @param $alias string
     * @return Validator instance
     */
    public function get(string $alias): Validator
    {
        if (!isset($this->alias2cls[$alias])) {
            throw new RepoException($alias . ' is yet registered');
        }

        $c = $this->alias2cls[$alias];
        $x = $this->cls2obj[$c];

        if ($x instanceof Validator) {
            return $x;
        }

        $this->cls2obj[$c] = new $x;
        return $this->cls2obj[$c];
    }

    /**
     * Main API entry.
     *
     *
     */
    public function check($val, string $alias, array $rules)
    {
        return $this->get($alias)->validate($this, $val, $rules);
    }
}
