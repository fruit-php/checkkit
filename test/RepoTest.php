<?php

namespace FruitTest\CheckKit;

use Fruit\CheckKit\Repo;

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

        $r->register('b', new \Fruit\CheckKit\Validators\StringValidator);
        $this->assertInstanceOf(
            'Fruit\CheckKit\Validators\StringValidator',
            $r->get('b')
        );

        $r->register('c', function () {
            return new \Fruit\CheckKit\Validators\StringValidator;
        });
        $this->assertInstanceOf(
            'Fruit\CheckKit\Validators\StringValidator',
            $r->get('c')
        );
    }

    public function invalidRegisterP()
    {
        return [
            ['a', 'Fruit\CheckKit\Validators\StringValidator'],
            ['d', new \DateTime],
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
}
