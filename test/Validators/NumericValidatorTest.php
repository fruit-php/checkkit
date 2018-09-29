<?php

namespace FruitTest\CheckKit\Validators;

use Fruit\CheckKit\Validators\NumericValidator as N;
use Fruit\CheckKit\Repo;

class NumericValidatorTest extends \PHPUnit\Framework\TestCase
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
            ['0xaef', self::ERR_TYPE, 'hexadecimal string'],
            ['0b11110101', self::ERR_TYPE, 'binary string'],
            ['02471', self::ERR_TYPE, 'octave string'],
            [null, self::ERR_TYPE, 'null'],
        ];
    }

    private function runner(array $rule, $data, string $expect, string $msg)
    {
        $n = new N;
        $actual = $n->validate(new Repo, $data, $rule);
        if ($expect === self::OK) {
            $this->assertNull($actual, $msg);
        } else {
            $this->assertInstanceOf($expect, $actual, $msg);
        }
    }

    public function defaultRuleP()
    {
        return $this->typingData([
            [1, self::OK, 'positive integer'],
            [0, self::OK, 'zero integer'],
            [-1, self::OK, 'negative integer'],
            [1.0, self::OK, 'positive float'],
            [0.0, self::OK, 'zero float'],
            [-1.0, self::OK, 'negative float'],
            ['1', self::OK, 'positive integer string'],
            ['0', self::OK, 'zero integer string'],
            ['-1', self::OK, 'negative integer string'],
            ['1.0', self::OK, 'positive float string'],
            ['0.0', self::OK, 'zero float string'],
            ['-1.0', self::OK, 'negative float string'],
            ['+0123.45e6', self::OK, 'example from php manual page'],
        ]);
    }

    /**
     * @dataProvider defaultRuleP
     */
    public function testDefaultRule($data, string $expect, string $msg)
    {
        $this->runner([], $data, $expect, $msg);
    }

    public function minMaxP()
    {
        return $this->typingData([
            [1.5, self::OK, 'in range'],
            [0, self::OK, 'in range'],
            [-1.5, self::OK, 'in range'],
            [2/3, self::OK, 'in range'],
            [-2/3, self::OK, 'in range'],
            [2, self::ERR_FORMAT, 'over max'],
            [-2, self::ERR_FORMAT, 'over min'],
            [1.50001, self::ERR_FORMAT, 'over max'],
            [-1.50001, self::ERR_FORMAT, 'over min'],
        ]);
    }

    /**
     * @dataProvider minMaxP
     */
    public function testMinMax($data, string $expect, string $msg)
    {
        $this->runner(['min' => -1.5, 'max' => 1.5], $data, $expect, $msg);
    }

    public function minMaxExcP()
    {
        return $this->typingData([
            [0, self::OK, 'in range'],
            [1.5, self::ERR_FORMAT, 'in range'],
            [-1.5, self::ERR_FORMAT, 'in range'],
            [4/3, self::OK, 'in range'],
            [-4/3, self::OK, 'in range'],
            [2, self::ERR_FORMAT, 'over max'],
            [-2, self::ERR_FORMAT, 'over min'],
            [1.50001, self::ERR_FORMAT, 'over max'],
            [-1.50001, self::ERR_FORMAT, 'over min'],
        ]);
    }

    /**
     * @dataProvider minMaxExcP
     */
    public function testMinExcMax($data, string $expect, string $msg)
    {
        $this->runner([
            'min' => -1.5,
            'max' => 1.5,
            'inc' => false
        ], $data, $expect, $msg);
    }

    public function invalidRuleP()
    {
        return [
            [['max' => 'str'], 'max is string'],
            [['min' => 'str'], 'min is string'],
            [[
                'max' => 1,
                'min' => 1.1
            ], 'min > max'],
        ];
    }

    /**
     * @dataProvider invalidRuleP
     * @expectedException \Fruit\CheckKit\Exceptions\InvalidRuleException
     */
    public function testInvalidRule($rule, string $msg)
    {
        (new N)->validate(new Repo, 1, $rule);
    }
}
