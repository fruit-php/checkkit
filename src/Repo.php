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
    private $cache = [];

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
    public function register(string $alias, $validator)
    {
        if (isset($this->cache[$alias])) {
            throw new RepoException($alias . ' is already registered');
        }

        if ($validator instanceof Validator) {
            $this->cache[$alias] = $validator;
            return;
        }

        if (is_callable($validator)) {
            $this->cache[$alias] = $validator;
            return;
        }

        if (is_string($validator) and class_exists($validator)) {
            $ref = new ReflectionClass($validator);
            if ($ref->isSubClassOf('Fruit\CheckKit\Validator')) {
                $this->cache[$alias] = $validator;
                return;
            }
        }

        throw new RepoException(
            $alias . ' is not registered. '
            . 'Need Validator instance, callable which generates Validator or full class name '
            . 'of Validator'
        );
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
        if (!isset($this->cache[$alias])) {
            throw new RepoException($alias . ' is yet registered');
        }

        $x = $this->cache[$alias];
        // type checking
        if ($x instanceof Validator) {
            return $x;
        }

        if (is_callable($x)) {
            $this->cache[$alias] = $x();
        } else {
            $this->cache[$alias] = new $x;
        }

        return $this->cache[$alias];
    }

    /**
     * Main API entry.
     *
     *
     */
    public function check($val, string $alias, array $rules)
    {
        return $this->get($alias)->validate($val, $rules);
    }
}
