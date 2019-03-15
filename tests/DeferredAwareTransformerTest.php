<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanLoad;
use Lneicelis\Transformer\Contract\CanPipe;
use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Pipe\LoaderPropertiesPipe;
use Lneicelis\Transformer\Pipe\TransformPipe;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Deferred;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class DeferredAwareTransformerTest extends TestCase
{
    /** @var TransformerRegistry */
    private $transformerRegistry;

    /** @var LoaderRegistry */
    private $loaderRegistry;

    /** @var CanPipe */
    private $transformPipe;

    /** @var LoaderPropertiesPipe */
    private $loadPropertiesPipe;

    /** @var DeferredAwareTransformer */
    private $transformer;

    public function setUp(): void
    {
        $this->transformerRegistry = new TransformerRegistry();
        $this->loaderRegistry = new LoaderRegistry();
        $this->transformPipe = new TransformPipe($this->transformerRegistry);
        $this->loadPropertiesPipe = new LoaderPropertiesPipe($this->loaderRegistry);

        $this->transformer = new DeferredAwareTransformer([
            $this->transformPipe,
            $this->loadPropertiesPipe,
        ], $this->loaderRegistry);
    }

    /** @test */
    public function itBatchLoadsDeferredValues(): void
    {
        $resources = [
            new stdClass(),
            new stdClass(),
        ];

        $this->transformerRegistry->addTransformer(new class implements CanTransform {
            public function getResourceClass(): string
            {
                return stdClass::class;
            }

            public function transform($resource)
            {
                return ['id' => 1];
            }
        });

        /** @var CanLoad|MockObject $loader */
        $loader = $this->createMock(CanLoad::class);
        $loader->expects(static::once())
            ->method('getResourceClass')
            ->willReturn(stdClass::class);
        $loader->expects(static::once())
            ->method('getProperty')
            ->willReturn('relation');
        $loader->expects(static::once())
            ->method('load')
            ->with([
                new Deferred($resources[0], 'relation'),
                new Deferred($resources[1], 'relation'),
            ])
            ->willReturn(['a', 'b']);

        $this->loaderRegistry->addLoader($loader);

        $expectedResult = [
            [
                'id' => 1,
                'relation' => 'a',
            ],
            [
                'id' => 1,
                'relation' => 'b',
            ],
        ];
        $actual = $this->transformer->transform($resources, new Context(['relation']));

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
