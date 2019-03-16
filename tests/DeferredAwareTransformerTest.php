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

        $this->transformerRegistry->addTransformer(new class implements CanTransform {
            public function getResourceClass(): string
            {
                return stdClass::class;
            }

            public function transform($resource)
            {
                return ['id' => $resource->id];
            }
        });
    }

    /** @test */
    public function itBatchLoadsDeferredValues(): void
    {
        $resources = [
            (object)['id' => 1],
            (object)['id' => 2],
        ];

        /** @var CanLoad|MockObject $loader */
        $loader = $this->createMock(CanLoad::class);
        $loader->expects(static::once())
            ->method('getResourceClass')
            ->willReturn(stdClass::class);
        $loader->expects(static::once())
            ->method('getProperty')
            ->willReturn('relation');
        $loader->expects(static::any())
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
                'id' => 2,
                'relation' => 'b',
            ],
        ];
        $actual = $this->transformer->transform($resources, new Context(['relation']));

        $this->assertEquals($expectedResult, $actual);
    }

    /** @test */
    public function itBatchLoadsDeferredValuesRecursively(): void
    {
        /** @var CanLoad|MockObject $loader */
        $loader = $this->createMock(CanLoad::class);
        $loader->expects(static::once())
            ->method('getResourceClass')
            ->willReturn(stdClass::class);
        $loader->expects(static::once())
            ->method('getProperty')
            ->willReturn('relation');

        $resources = (object)['id' => 1];
        $childResource = (object)['id' => 2];
        $loader->expects(static::exactly(2))
            ->method('load')
            ->withConsecutive(
                [[new Deferred($resources, 'relation')]],
                [[new Deferred($childResource, 'relation')]]
            )
            ->willReturnOnConsecutiveCalls(
                [$childResource],
                ['a']
            );

        $this->loaderRegistry->addLoader($loader);

        $expectedResult = [
            'id' => 1,
            'relation' => [
                'id' => 2,
                'relation' => 'a',
            ],
        ];
        $schema = [
            'relation' => [
                'relation',
            ]
        ];
        $actual = $this->transformer->transform($resources, new Context($schema));

        $this->assertEquals($expectedResult, $actual);
    }
}
