<?php

namespace Lneicelis\Transformer\Helper;

use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    /** @test */
    public function itSetValueOnReturnedArray(): void
    {
        $array = [
            'a' => [
                'b' => [1]
            ]
        ];
        $expected = [
            'a' => [
                'b' => [1, 'c']
            ]
        ];
        $actual = Arr::setValue($array, ['a', 'b', 1], 'c');

        $this->assertNotEquals($array, $expected);
        $this->assertEquals($expected, $actual);
    }
}
