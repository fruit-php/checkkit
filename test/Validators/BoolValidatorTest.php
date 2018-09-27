<?php

namespace FruitTest\CheckKit\Validators;

use Fruit\CheckKit\Validators\BoolValidator as B;

class BoolValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function defaultRuleP()
    {
        return [
            [true, true, 'true'],
            [false, true, 'false'],
            ['true', false, 'true string'],
            ['false', false, 'false string'],
            [1, false, 'integer 1'],
            [-1, false, 'integer -1'],
            [0, false, 'integer 0'],
            [new \DateTime, false, 'object'],
            [null, false, 'null'],
        ];
    }

    /**
     * @dataProvider defaultRuleP
     */
    public function testDefaultRule($data, bool $expect, string $msg)
    {
        $actual = (new B)->validate($data, []);
        if ($expect) {
            $this->assertNull($actual, $msg);
        } else {
            $this->assertInstanceOf(
                'Fruit\CheckKit\Exceptions\InvalidTypeException',
                $actual,
                $msg
            );
        }
    }
}
