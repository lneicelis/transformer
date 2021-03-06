<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Exception\DuplicateResourceTransformerException;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class TransformerRegistryTest extends TestCase
{
    /** @var TransformerRegistry */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new TransformerRegistry();
    }

    /** @test */
    public function itReturnsSourceTransformer(): void
    {
        $transformer = new class implements CanTransform {
            public function getResourceClass(): string
            {
                return stdClass::class;
            }

            public function transform($resource)
            {
                return null;
            }
        };

        $this->instance->addTransformer($transformer);

        $this->assertEquals($transformer, $this->instance->getTransformer(new stdClass()));
    }

    /** @test */
    public function itUsesInheritanceTreeToResolveSourceTransformer(): void
    {
        $transformer = new class implements CanTransform {
            public function getResourceClass(): string
            {
                return stdClass::class;
            }

            public function transform($resource)
            {
                return null;
            }
        };

        $resource = $this->createMock(stdClass::class);

        $this->instance->addTransformer($transformer);

        $this->assertInstanceOf(MockObject::class, $resource);

        $this->assertEquals($transformer, $this->instance->getTransformer($resource));
    }

    /** @test */
    public function itThrowsWhenTransformerIsMissing(): void
    {
        $this->expectException(TransformerNotFoundException::class);

        $this->instance->getTransformer(new stdClass());
    }

    /** @test */
    public function itThrowsWhenTransformerForSourceAlreadyExists(): void
    {
        $transformer = new class implements CanTransform {
            public function getResourceClass(): string
            {
                return stdClass::class;
            }

            public function transform($resource)
            {
                return null;
            }
        };

        $this->instance->addTransformer($transformer);

        $this->expectException(DuplicateResourceTransformerException::class);

        $this->instance->addTransformer($transformer);
    }
}
