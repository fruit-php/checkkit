<?php

namespace FruitTest\CheckKit\Validators;

use Fruit\CheckKit\Validators\DictValidator as D;
use Fruit\CheckKit\Repo;

class DictValidatorTest extends \PHPUnit\Framework\TestCase
{
    const ERR_TYPE = 'Fruit\CheckKit\Exceptions\InvalidTypeException';
    const ERR_FORMAT = 'Fruit\CheckKit\Exceptions\InvalidFormatException';
    const OK = '';

    private function typingData(array $data): array
    {
        return $data + [
            [new \DateTime, self::ERR_TYPE, 'datetime object'],
            ['string', self::ERR_TYPE, 'string'],
            ['true', self::ERR_TYPE, 'boolean string'],
            [true, self::ERR_TYPE, 'boolean'],
            ['1.0', self::ERR_TYPE, 'float string'],
            [1, self::ERR_TYPE, 'int'],
            [0, self::ERR_TYPE, 'int zero'],
            [null, self::ERR_TYPE, 'null'],
        ];
    }

    private function repo(): Repo
    {
        $ret = new Repo;
        $ret->register('int', 'Fruit\CheckKit\Validators\IntValidator');
        $ret->register('string', 'Fruit\CheckKit\Validators\StringValidator');
        $ret->register('array', 'Fruit\CheckKit\Validators\ArrayValidator');

        return $ret;
    }

    private function runner(array $rule, $data, string $expect, string $msg)
    {
        $actual = (new D)->validate($this->repo(), $data, $rule);
        if ($expect === self::OK) {
            $this->assertNull($actual, $msg);
        } elseif (class_exists($expect)) {
            $this->assertInstanceOf($expect, $actual, $msg);
        } else {
            $this->assertInstanceOf(
                'Fruit\CheckKit\Exceptions\InvalidElementException',
                $actual,
                $msg
            );

            $this->assertEquals($expect, $actual->key, $msg);
        }
    }

    public function defaultRuleP()
    {
        return $this->typingData([
            [['a' => 1], self::OK, 'simple dict'],
            [[], self::OK, 'empty array'],
            [['1' => 1], self::OK, 'numeric string key'],
            [[1,2,3], self::OK, 'indexed array'],
        ]);
    }

    /**
     * @dataProvider defaultRuleP
     */
    public function testDefaultRule($data, string $expect, string $msg)
    {
        $this->runner([], $data, $expect, $msg);
    }

    public function requiredKeyP()
    {
        return $this->typingData([
            [['3' => 1], self::OK, 'fulfill'],
            [['b' => 1], '3', 'without'],
            [[1, 2, 3], '3', 'without (indexed)'],
            [[1, 2, 3, 4], self::OK, 'fulfill (indexed)'],
        ]);
    }

    /**
     * @dataProvider requiredKeyP
     */
    public function testRequiredKey($data, string $expect, string $msg)
    {
        $this->runner([
            'elements' => [
                '3' => ['required' => true],
            ],
        ], $data, $expect, $msg);
    }

    public function lengthP()
    {
        return $this->typingData([
            [['a' => 1, 'b' => 2], self::OK, 'min'],
            [['a' => 1, 'b' => 2, 'c' => 3], self::OK, 'max'],
            [[1, 2], self::OK, 'min (indexed)'],
            [[1, 2, 3], self::OK, 'max (indexed)'],
            [['a' => 1], self::ERR_FORMAT, '<min'],
            [['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], self::ERR_FORMAT, '>max'],
            [[1], self::ERR_FORMAT, '<min (indexed)'],
            [[1, 2, 3, 4], self::ERR_FORMAT, '>max (indexed)'],
        ]);
    }

    /**
     * @dataProvider lengthP
     */
    public function testLength($data, string $expect, string $msg)
    {
        $this->runner([
            'min_length' => 2,
            'max_length' => 3,
        ], $data, $expect, $msg);
    }

    public function elementNonStrictP()
    {
        return [
            [[ 'elements' => [
                'a' => [ 'type' => 'int' ],
                'b' => [ 'type' => 'string' ],
            ]], self::OK, 'has extra key'],
            [[ 'elements' => [
                'a' => [],
            ]], self::OK, 'check only key exists'],
            [[ 'elements' => [
                'a' => [
                    'type' => 'int',
                    'rules' => ['min' => 1],
                ],
            ]], self::OK, 'pass args to next validator'],
            [[ 'elements' => [
                'a' => [],
                'd' => [],
            ]], self::OK, 'missing optional key'],
            [[ 'elements' => [
                'a' => [],
                'd' => [ 'required' => true ],
            ]], 'd', 'missing required key'],
            [[ 'elements' => [
                'a' => [ 'type' => 'int' ],
                'b' => [ 'type' => 'int' ],
            ]], 'b', 'type mismatch'],
            [[ 'elements' => [
                'a' => [
                    'type' => 'int',
                    'rules' => ['min' => 10],
                ],
            ]], 'a', 'pass args to next validator and failed'],
        ];
    }

    /**
     * @dataProvider elementNonStrictP
     */
    public function testElementNonStrict(array $rule, string $expect, string $msg)
    {
        $this->runner($rule, [
            'a' => 1,
            'b' => 'string',
            'c' => [1, 2, 3],
        ], $expect, $msg);

        foreach ($this->typingData([]) as $r) {
            $this->runner($rule, $r[0], $r[1], $r[2]);
        }
    }

    public function elementStrictP()
    {
        return [
            [[ 'elements' => [
                'a' => [ 'type' => 'int' ],
                'b' => [ 'type' => 'string' ],
                'c' => [],
            ], 'strict' => true], self::OK, 'just fit'],
            [[ 'elements' => [
                'a' => [],
                'c' => [],
            ], 'strict' => true], 'b', 'has extra key'],
            [[ 'elements' => [
                'a' => [],
                'b' => [],
                'c' => [],
                'd' => [],
            ], 'strict' => true], self::OK, 'missing optional key'],
            [[ 'elements' => [
                'a' => [
                    'type' => 'int',
                    'rules' => [ 'min' => 1 ],
                ],
                'b' => [ 'type' => 'string' ],
                'c' => [],
            ], 'strict' => true], self::OK, 'pass args to next validator'],
            [[ 'elements' => [
                'a' => [],
                'b' => [],
                'c' => [],
                'd' => [ 'required' => true ],
            ], 'strict' => true], 'd', 'missing required key'],
            [[ 'elements' => [
                'a' => [ 'type' => 'int' ],
                'b' => [ 'type' => 'int' ],
                'c' => [],
            ], 'strict' => true], 'b', 'type mismatch'],
            [[ 'elements' => [
                'a' => [
                    'type' => 'int',
                    'rules' => [ 'min' => 10 ],
                ],
                'b' => [ 'type' => 'string' ],
                'c' => [],
            ], 'strict' => true], 'a', 'pass args to next validator but fail'],
        ];
    }

    /**
     * @dataProvider elementStrictP
     */
    public function testElementStrict(array $rule, string $expect, string $msg)
    {
        $this->runner($rule, [
            'a' => 1,
            'b' => 'string',
            'c' => [1, 2, 3],
        ], $expect, $msg);

        foreach ($this->typingData([]) as $r) {
            $this->runner($rule, $r[0], $r[1], $r[2]);
        }
    }

    public function catchAllP()
    {
        return [
            [[ 'elements' => [
                '*' => [],
                'b' => [ 'type' => 'string' ],
            ]], self::OK, 'just fit (no type)'],
            [[ 'elements' => [
                '*' => [ 'type' => 'int' ],
                'b' => [ 'type' => 'string' ],
            ]], self::OK, 'just fit (w/ type)'],
            [[ 'elements' => [
                '*' => [],
                'b' => [ 'type' => 'string' ],
            ], 'strict' => true ], self::OK, 'just fit (no type/strict)'],
            [[ 'elements' => [
                '*' => [ 'type' => 'int' ],
                'b' => [ 'type' => 'string' ],
            ], 'strict' => true ], self::OK, 'just fit (w/ type/strict)'],
            [[ 'elements' => [
                '*' => [ 'regex' => 'a[0-9]+' ],
                'b' => [ 'type' => 'string' ],
            ]], self::OK, 'regex'],
            [[ 'elements' => [
                '*' => [ 'regex' => 'a[0-9]+' ],
                'b' => [ 'type' => 'string' ],
            ], 'strict' => true ], self::OK, 'regex (strict)'],
            [[ 'elements' => [
                '*' => [ 'type' => 'string' ],
                'b' => [ 'type' => 'string' ],
            ]], 'a1', 'type mismatch'],
            [[ 'elements' => [
                '*' => [ 'type' => 'string' ],
                'b' => [ 'type' => 'string' ],
            ], 'strict' => true], 'a1', 'type mismatch (strict)'],
            [[ 'elements' => [
                '*' => [ 'regex' => 'a[2-9]+' ],
                'b' => [ 'type' => 'string' ],
            ]], 'a1', 'regex (failed)'],
            [[ 'elements' => [
                '*' => [ 'regex' => 'a[2-9]+' ],
                'b' => [ 'type' => 'string' ],
            ], 'strict' => true ], 'a1', 'regex (strict/failed)'],
        ];
    }

    /**
     * @dataProvider catchAllP
     */
    public function testCatchAll(array $rule, string $expect, string $msg)
    {
        $this->runner($rule, [
            'a1' => 1,
            'a2' => 2,
            'b' => 'string',
        ], $expect, $msg);

        foreach ($this->typingData([]) as $r) {
            $this->runner($rule, $r[0], $r[1], $r[2]);
        }
    }

    public function testRequiredCatchAll()
    {
        $rule = [
            'elements' => [
                '*' => [ 'required' => true ],
                'b' => [ 'type' => 'string' ],
            ]
        ];
        $val = [];

        $this->runner($rule, $val, self::OK, 'empty');
    }
}
