<?php

namespace FruitTest\CheckKit\Validators;

use Fruit\CheckKit\Validators\ArrayValidator as A;
use Fruit\CheckKit\Repo;

class ArrayValidatorTest extends \PHPUnit\Framework\TestCase
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

    private function runner(array $rule, $data, string $expect, string $msg)
    {
        $actual = (new A)->validate(new Repo, $data, $rule);
        if ($expect === self::OK) {
            $this->assertNull($actual, $msg);
        } else {
            $this->assertInstanceOf($expect, $actual, $msg);
        }
    }

    public function defaultRuleP()
    {
        return $this->typingData([
            [[], self::OK, 'empty array'],
            [[1], self::OK, 'small array'],
            [[1, 2, 'asd'], self::OK, 'mixed array'],
            [[
                0 => 'a',
                1 => 'b',
            ], self::OK, 'var_exported array'],
            [[
                0 => 'a',
                2 => 'b',
            ], self::ERR_TYPE, 'skiped array'],
        ]);
    }

    /**
     * @dataProvider defaultRuleP
     */
    public function testDefaultRule($data, string $expect, string $msg)
    {
        $this->runner([], $data, $expect, $msg);
    }

    public function nonStrictP()
    {
        return $this->typingData([
            [[], self::OK, 'empty array'],
            [[1], self::OK, 'small array'],
            [[1, 2, 'asd'], self::OK, 'mixed array'],
            [[
                0 => 'a',
                1 => 'b',
            ], self::OK, 'var_exported array'],
            [[
                0 => 'a',
                2 => 'b',
            ], self::OK, 'skiped array'],
        ]);
    }

    /**
     * @dataProvider nonStrictP
     */
    public function testNonStrict($data, string $expect, string $msg)
    {
        $this->runner(['strict' => false], $data, $expect, $msg);
    }

    public function minMaxP()
    {
        return $this->typingData([
            [[1, 2, 'asd'], self::OK, 'mixed array'],
            [[
                0 => 'a',
                1 => 'b',
            ], self::OK, 'var_exported array'],
            [[
                0 => 'a',
                2 => 'b',
            ], self::ERR_TYPE, 'skiped array'],
            [[1, 2, 'asd', 4], self::ERR_FORMAT, 'long array'],
            [[
                0 => 'a',
            ], self::ERR_FORMAT, 'short array'],
            [[
                1 => 'a',
            ], self::ERR_FORMAT, 'skiped short array'],
        ]);
    }

    /**
     * @dataProvider minMaxP
     */
    public function testMinMax($data, string $expect, string $msg)
    {
        $this->runner([
            'min_length' => 2,
            'max_length' => 3,
        ], $data, $expect, $msg);
    }

    public function minMaxNonStrictP()
    {
        return $this->typingData([
            [[1, 2, 'asd'], self::OK, 'mixed array'],
            [[
                0 => 'a',
                1 => 'b',
            ], self::OK, 'var_exported array'],
            [[
                0 => 'a',
                2 => 'b',
            ], self::OK, 'skiped array'],
            [[1, 2, 'asd', 4], self::ERR_FORMAT, 'long array'],
            [[
                0 => 'a',
            ], self::ERR_FORMAT, 'short array'],
            [[
                1 => 'a',
            ], self::ERR_FORMAT, 'skiped short array'],
        ]);
    }

    /**
     * @dataProvider minMaxNonStrictP
     */
    public function testMinMaxNonStrict($data, string $expect, string $msg)
    {
        $this->runner([
            'strict' => false,
            'min_length' => 2,
            'max_length' => 3,
        ], $data, $expect, $msg);
    }

    public function invalidRuleP()
    {
        return [
            [['max_length' => 'str'], 'max is string'],
            [['min_length' => 'str'], 'min is string'],
            [['max_length' => 0], 'max < 1'],
            [['min_length' => -1], 'min < 0'],
            [['max_length' => 1.0], 'max is float'],
            [['min_length' => 1.0], 'min is float'],
            [[
                'max_length' => 1,
                'min_length' => 2
            ], 'min > max'],
        ];
    }

    /**
     * @dataProvider invalidRuleP
     * @expectedException \Fruit\CheckKit\Exceptions\InvalidRuleException
     */
    public function testInvalidRule($rule, string $msg)
    {
        (new A)->validate(new Repo, [1, 2, 3], $rule);
    }

    public function testData()
    {
        $repo = new Repo;
        $repo->register('int', 'Fruit\CheckKit\Validators\IntValidator');
        $val = [1, 2, '3'];
        $rule = ['data' => 'int'];
        $actual = (new A)->validate($repo, $val, $rule);

        $this->assertInstanceOf(
            'Fruit\CheckKit\Exceptions\InvalidElementException',
            $actual
        );

        $this->assertEquals('2', $actual->key);
    }
}
