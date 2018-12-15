<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanLoad;
use Lneicelis\Transformer\ValueObject\Deferred;
use PHPUnit\Framework\TestCase;
use stdClass;

class DeferredAwareTransformerTest extends TestCase
{
    /** @var DeferredAwareTransformer */
    private $transformer;

    public function setUp(): void
    {
        $this->transformer = new DeferredAwareTransformer();
    }

    /** @test */
    public function itBatchLoadsDeferredValues(): void
    {
        $loader = $this->createMock(CanLoad::class);
        $loader->expects(static::once())
            ->method('getResourceName')
            ->willReturn(stdClass::class);
        $loader->expects(static::once())
            ->method('load')
            ->with([1, 2])
            ->willReturn(['a', 'b']);

        $this->transformer->addLoader($loader);

        $input = [
            ['resource' => new Deferred(stdClass::class, 1)],
            ['resource' => new Deferred(stdClass::class, 2)],
        ];
        $expectedResult = [
            ['resource' => 'a'],
            ['resource' => 'b'],
        ];
        $actual = $this->transformer->transform($input);

        $this->assertEquals($expectedResult, $actual);
    }

    /** @test */
    public function itBatchLoadsDeferredValuesRecursively(): void
    {
        $loader = $this->createMock(CanLoad::class);
        $loader->expects(static::once())
            ->method('getResourceName')
            ->willReturn('Category');
        $loader->expects(static::at(1))
            ->method('load')
            ->with([3, 4])
            ->willReturn([
                ['id' => 3, 'parent' => new Deferred('Category', 1)],
                ['id' => 4, 'parent' => new Deferred('Category', 2)],
            ]);
        $loader->expects(static::at(2))
            ->method('load')
            ->willReturn([
                'category1',
                'category2',
            ]);

        $this->transformer->addLoader($loader);

        $input = [
            ['category' => new Deferred('Category', 3)],
            ['category' => new Deferred('Category', 4)],
        ];
        $expectedResult = [
            [
                'category' => [
                    'id' => 3,
                    'parent' => 'category1',
                ]
            ],
            [
                'category' => [
                    'id' => 4,
                    'parent' => 'category2',
                ]
            ],
        ];
        $actual = $this->transformer->transform($input);

        $this->assertEquals($expectedResult, $actual);
    }
}
