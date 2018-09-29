<?php

namespace FruitTest\CheckKit\Validators;

use Fruit\CheckKit\Validators\StringValidator as S;
use Fruit\CheckKit\Repo;

class StringValidatorTest extends \PHPUnit\Framework\TestCase
{
    const ERR_TYPE = 'Fruit\CheckKit\Exceptions\InvalidTypeException';
    const ERR_FORMAT = 'Fruit\CheckKit\Exceptions\InvalidFormatException';
    const OK = '';

    private function typingData(array $data): array
    {
        return $data + [
            [new \DateTime, self::ERR_TYPE, 'datetime object'],
            [1, self::ERR_TYPE, 'integer'],
            [1.2, self::ERR_TYPE, 'float'],
            [true, self::ERR_TYPE, 'boolean'],
            [null, self::ERR_TYPE, 'null'],
        ];
    }

    private function runner(array $rule, $data, string $expect, string $msg)
    {
        $s = new S;
        $actual = $s->validate(new Repo, $data, $rule);
        if ($expect === self::OK) {
            $this->assertNull($actual, $msg);
        } else {
            $this->assertInstanceOf($expect, $actual, $msg);
        }
    }

    public function defaultRuleP()
    {
        return $this->typingData([
            ['string', self::OK, 'string'],
            [str_repeat(' ', 10000), self::OK, 'super long string (10000 spaces)'],
            ['', self::OK, 'empty string'],
            ['1', self::OK, 'integer string'],
            ['true', self::OK, 'boolean string'],
            ['!@#%$(){}[]\'"中文', self::OK, 'string with special characters'],
        ]);
    }

    /**
     * @dataProvider defaultRuleP
     */
    public function testDefaultRule($data, string $expect, string $msg)
    {
        $this->runner([], $data, $expect, $msg);
    }

    public function emptyP()
    {
        return $this->typingData([
            ['', self::ERR_FORMAT, 'empty string'],
            ['string', self::OK, 'string'],
            ['!@#%$(){}[]\'"中文', self::OK, 'string with special characters'],
            [str_repeat(' ', 10000), self::OK, 'super long string (10000 spaces)'],
        ]);
    }

    /**
     * @dataProvider emptyP
     */
    public function testEmpty($data, string $expect, string $msg)
    {
        $this->runner([
            'empty' => false,
        ], $data, $expect, $msg);
    }

    public function minLengthP()
    {
        return $this->typingData([
            ['', self::ERR_FORMAT, 'empty string'],
            [' ', self::ERR_FORMAT, 'short string'],
            ['  ', self::OK, 'just fit'],
            [str_repeat(' ', 10000), self::OK, 'super long string (10000 spaces)'],
            ['中', self::ERR_FORMAT, 'short string with special charracter'],
        ]);
    }

    /**
     * @dataProvider minLengthP
     */
    public function testMinLength($data, string $expect, string $msg)
    {
        $this->runner([
            'min_length' => 2,
        ], $data, $expect, $msg);

        if ($data === '') {
            $expect = self::ERR_FORMAT;
        }
        $this->runner([
            'min_length' => 2,
            'empty' => false,
        ], $data, $expect, $msg);
    }

    public function maxLengthP()
    {
        return $this->typingData([
            ['', self::OK, 'empty string'],
            [' ', self::OK, 'short string'],
            ['  ', self::ERR_FORMAT, 'just over'],
            [str_repeat(' ', 10000), self::ERR_FORMAT, 'super long string (10000 spaces)'],
            ['中', self::OK, 'short string with special charracter'],
            ['中文字', self::ERR_FORMAT, 'long string with special charracter'],
        ]);
    }

    /**
     * @dataProvider maxLengthP
     */
    public function testMaxLength($data, string $expect, string $msg)
    {
        $this->runner([
            'max_length' => 1,
        ], $data, $expect, $msg);
    }

    public function minMaxLengthP()
    {
        return $this->typingData([
            [' ', self::ERR_FORMAT, 'short string'],
            ['  ', self::OK, 'just min'],
            ['    ', self::OK, 'just max'],
            ['     ', self::ERR_FORMAT, 'long string'],
            ['中', self::ERR_FORMAT, 'short string with special charracter'],
            ['中文', self::OK, 'just min with special charracter'],
            ['中  文', self::OK, 'just max with special charracter'],
            ['中   文', self::ERR_FORMAT, 'long string with special charracter'],
        ]);
    }

    /**
     * @dataProvider minMaxLengthP
     */
    public function testMinMaxLength($data, string $expect, string $msg)
    {
        $this->runner([
            'min_length' => 2,
            'max_length' => 4,
        ], $data, $expect, $msg);
    }

    public function regexpP()
    {
        return [
            [[
                'regex' => '123',
            ], self::OK, 'part match from begining'],
            [[
                'regex' => '.*tRue',
            ], self::OK, 'part match from middle'],
            [[
                'regex' => '.{9}$',
            ], self::OK, 'match whole string'],
            [[
                'regex' => '[0-9]+[a-z]+',
                'regex_mode' => 'i',
            ], self::OK, 'use regexp mode'],
            [[
                'regex' => '[0-9a-z中文]+',
                'regex_mode' => 'i',
            ], self::OK, 'with special character'],
            [[
                'regex' => '[0-9a-z中文]+',
                'regex_mode' => 'i',
                'min_length' => 5,
            ], self::OK, 'with min length'],
            [[
                'regex' => '[0-9a-z中文]+',
                'regex_mode' => 'i',
                'min_length' => 100,
            ], self::ERR_FORMAT, 'over min length'],
            [[
                'regex' => '[0-9a-z中文]+',
                'regex_mode' => 'i',
                'max_length' => 100,
            ], self::OK, 'with max length'],
            [[
                'regex' => '[0-9a-z中文]+',
                'regex_mode' => 'i',
                'max_length' => 5,
            ], self::ERR_FORMAT, 'over max length'],
            [[
                'regex' => '[a-z]+$',
            ], self::ERR_FORMAT, 'not match'],
        ];
    }

    /**
     * @dataProvider regexpP
     */
    public function testRegexp(array $rule, string $expect, string $msg)
    {
        $s = new S;

        // test against typing error
        foreach ($this->typingData([]) as $data) {
            $actual = $s->validate(new Repo, $data[0], $rule);
            $this->assertInstanceOf(self::ERR_TYPE, $actual, $data[2]);
        }

        $actual = $s->validate(new Repo, '123tRue中文', $rule);
        if ($expect === self::OK) {
            $this->assertNull($actual, $msg);
        } else {
            $this->assertInstanceOf($expect, $actual, $msg);
        }
    }

    public function invalidRuleP()
    {
        return [
            [['max_length' => 0], 'max length < 1'],
            [['min_length' => 0], 'min length < 1'],
            [['max_length' => '1'], 'max length is string'],
            [['min_length' => '1'], 'mis length is string'],
            [['max_length' => 1.0], 'max length is float'],
            [['min_length' => 1.0], 'mis length is float'],
            [['max_length' => true], 'max length is boolean'],
            [['min_length' => true], 'mis length is boolean'],
            [['max_length' => new \DateTime], 'max length is object'],
            [['min_length' => new \DateTime], 'mis length is object'],
            [['max_length' => []], 'max length is array'],
            [['min_length' => []], 'mis length is array'],
            [[
                'min_length' => 5,
                'max_length' => 4,
            ], 'min length > max length'],
        ];
    }

    /**
     * @dataProvider invalidRuleP
     * @expectedException \Fruit\CheckKit\Exceptions\InvalidRuleException
     */
    public function testInvalidRule($rule, string $msg)
    {
        (new S)->validate(new Repo, '', $rule);
    }
}
