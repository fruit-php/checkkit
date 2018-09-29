<?php

namespace FruitTest\CheckKit\Validators;

use Fruit\CheckKit\Validators\IntValidator as I;
use Fruit\CheckKit\Repo;

class IntValidatorTest extends \PHPUnit\Framework\TestCase
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
            ['1', self::ERR_TYPE, 'integer string'],
            [1.0, self::ERR_TYPE, 'float'],
            ['+1e2', self::ERR_TYPE, 'numeric string represents integer'],
            [null, self::ERR_TYPE, 'null'],
        ];
    }

    private function runner(array $rule, $data, string $expect, string $msg)
    {
        $n = new I;
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
            [1, self::OK, 'in range'],
            [0, self::OK, 'in range'],
            [-1, self::OK, 'in range'],
            [2, self::ERR_FORMAT, 'over max'],
            [-2, self::ERR_FORMAT, 'over min'],
        ]);
    }

    /**
     * @dataProvider minMaxP
     */
    public function testMinMax($data, string $expect, string $msg)
    {
        $this->runner(['min' => -1, 'max' => 1], $data, $expect, $msg);
    }

    /**
     * @dataProvider minMaxP
     */
    public function testMinMaxExc($data, string $expect, string $msg)
    {
        $this->runner([
            'min' => -2,
            'max' => 2,
            'inc' => false
        ], $data, $expect, $msg);
    }

    public function invalidRuleP()
    {
        return [
            [['max' => 'str'], 'max is string'],
            [['min' => 'str'], 'min is string'],
            [['max' => 1.1], 'max is float'],
            [['min' => 1.1], 'min is float'],
            [[
                'max' => 1,
                'min' => 2
            ], 'min > max'],
        ];
    }

    /**
     * @dataProvider invalidRuleP
     * @expectedException \Fruit\CheckKit\Exceptions\InvalidRuleException
     */
    public function testInvalidRule($rule, string $msg)
    {
        (new I)->validate(new Repo, 1, $rule);
    }
}
