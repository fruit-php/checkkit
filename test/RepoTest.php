<?php

namespace FruitTest\CheckKit;

use Fruit\CheckKit\Repo;
use ReflectionClass;

class RepoTest extends \PHPUnit\Framework\TestCase
{
    public function testRegister()
    {
        $r = new Repo;
        $r->register('a', 'Fruit\CheckKit\Validators\StringValidator');
        $this->assertInstanceOf(
            'Fruit\CheckKit\Validators\StringValidator',
            $r->get('a')
        );
    }

    public function invalidRegisterP()
    {
        return [
            ['a', 'Fruit\CheckKit\Validators\StringValidator'],
            ['d', '\DateTime'],
        ];
    }

    /**
     * @dataProvider invalidRegisterP
     * @expectedException \Fruit\CheckKit\Exceptions\RepoException
     */
    public function testInvalidRegister($alias, $v)
    {
        $r = new Repo;
        $r->register('a', 'Fruit\CheckKit\Validators\StringValidator');
        $r->register($alias, $v);
    }

    public function testGet()
    {
        $r = new Repo;
        $r->register('test', 'Fruit\CheckKit\Validators\StringValidator');
        $this->assertInstanceOf(
            'Fruit\CheckKit\Validators\StringValidator',
            $r->get('test')
        );
    }

    /**
     * @expectedException \Fruit\CheckKit\Exceptions\RepoException
     */
    public function testInvalidGet()
    {
        (new Repo)->get('testee');
    }

    public function testCompiledClass()
    {
        $repo = Repo::default();
        $expect = (new ReflectionClass($repo->get('int')))->getName();
        $code = '$actual = ' . $repo->compile()->render() . ';';
        eval($code);
        $this->assertInstanceOf($expect, $actual->get('int'));
    }
}
