<?php

namespace Fruit\CheckKit;

use Fruit\CheckKit\Exceptions\RepoException;
use ReflectionClass;
use Fruit\CompileKit\Compilable;
use Fruit\CompileKit\Renderable;
use Fruit\CompileKit\AnonymousClass;
use Fruit\CompileKit\Block;
use Fruit\CompileKit\Value;

/**
 * Repo is the main API entry of CheckKit.
 *
 * It is designed to support lazy-load without compilation. See Repo::register.
 */
class Repo implements Compilable
{
    protected $cls2obj = [];
    protected $alias2cls = [];

    /**
     * Create an repo with default validators. This is a wrapper for Repo::setDefault.
     *
     * @return Repo instance
     */
    public static function default(): Repo
    {
        return (new Repo)->setDefault();
    }

    /**
     * Add predefined validators. THIS METHOD OVERWRITES EXISTING ALIAS SILENTLY.
     *
     * All validators within Validators subdir are registered with their name casted
     * to lowercase, without "Validator" suffix. For example, `$repo->get('int')`
     * returns Validators::IntValidator.
     *
     * @return Repo instance
     */
    public function setDefault(): self
    {
        $v = ['Array', 'Bool', 'Dict', 'Float', 'Int', 'Numeric', 'String'];
        foreach ($v as $c) {
            $k = strtolower($c);
            $cls = 'Fruit\CheckKit\Validators\\' . $c . 'Validator';
            if (!isset($this->cls2obj[$k])) {
                $this->cls2obj[$cls] = $cls;
            }

            $this->alias2cls[$k] = $cls;
        }

        return $this;
    }

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
     */
    public function check($val, string $alias, array $rules)
    {
        return $this->get($alias)->validate($this, $val, $rules);
    }

    /**
     * Main API entry.
     *
     * This method is identical to Repo::check, but throws returned exception.
     *
     * @throws Exception
     */
    public function mustCheck($val, string $alias, array $rules)
    {
        $ret = $this->get($alias)->validate($this, $val, $rules);
        if ($ret !== null) {
            throw $ret;
        }
    }

    /**
     * Implementing Compilable interface.
     *
     * It always return an AnonymousClass, and Repo::register is masked. Calling
     * register is no-op.
     *
     * @see Fruit::CompileKit::Compilable
     */
    public function compile(): Renderable
    {
        $c2o = [];
        $a2c = [];
        foreach ($this->cls2obj as $k => $v) {
            $c2o[$k] = $k;
        }
        foreach ($this->alias2cls as $k => $v) {
            $a2c[$k] = $v;
        }

        $body = (new Block)
            ->append(Value::assign(
                Value::as('$this->cls2obj'),
                Value::of($c2o)
            ))
            ->append(Value::assign(
                Value::as('$this->alias2cls'),
                Value::of($a2c)
            ));
        $ret = new AnonymousClass;
        $ret
            ->extends('\Fruit\CheckKit\Repo')
            ->can('__construct')
            ->append($body);
        $ret
            ->can('register')
            ->rawArg('$a', 'string')
            ->rawArg('$b', 'string');

        return $ret;
    }
}
