<?php

namespace Lneicelis\Transformer\Pipe;

use Lneicelis\Transformer\Contract\CanGuard;
use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Contract\HasAccessControl;
use Lneicelis\Transformer\Exception\AccessDeniedException;
use Lneicelis\Transformer\TransformerRepository;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Path;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class AccessControlPipeTest extends TestCase
{
    /** @var TransformerRepository|MockObject */
    private $transformerRepository;

    /** @var CanGuard|MockObject */
    private $testGuard;

    /** @var AccessControlPipe */
    private $instance;

    protected function setUp(): void
    {
        $this->transformerRepository = $this->createMock(TransformerRepository::class);
        $this->testGuard = $this->createMock(CanGuard::class);
        $this->instance = new AccessControlPipe($this->transformerRepository);
    }

    /** @test */
    public function itAllowsAccessWhenNoGuardSpecified(): void
    {
        $transformer = $this
            ->getMockBuilder([CanTransform::class, HasAccessControl::class])
            ->getMock();
        $transformer->expects(static::once())
            ->method('getDefaultAcl')
            ->willReturn([]);

        $this->transformerRepository->expects(static::once())
            ->method('getTransformer')
            ->willReturn($transformer);

        $this->instance->pipe(new stdClass(), new Context(), new Path(), null);
    }

    /** @test */
    public function itAllowsWhenGuardAllows(): void
    {
        $source = new stdClass();
        $context = new Context();

        $transformer = $this
            ->getMockBuilder([CanTransform::class, HasAccessControl::class])
            ->getMock();
        $transformer->expects(static::once())
            ->method('getDefaultAcl')
            ->willReturn(['test_guard']);

        $this->transformerRepository->expects(static::once())
            ->method('getTransformer')
            ->willReturn($transformer);

        $this->testGuard->expects(static::once())
            ->method('getName')
            ->willReturn('test_guard');
        $this->testGuard->expects(static::once())
            ->method('canAccess')
            ->with($source, $context)
            ->willReturn(true);

        $this->instance->addGuard($this->testGuard);

        $this->instance->pipe(new stdClass(), new Context(), new Path(), null);
    }

    /** @test */
    public function itThrowsIfGuardDoesNotAllowAccess(): void
    {
        $transformer = $this
            ->getMockBuilder([CanTransform::class, HasAccessControl::class])
            ->getMock();
        $transformer->expects(static::once())
            ->method('getDefaultAcl')
            ->willReturn(['test_guard']);

        $this->transformerRepository->expects(static::once())
            ->method('getTransformer')
            ->willReturn($transformer);

        $this->testGuard->expects(static::once())
            ->method('getName')
            ->willReturn('test_guard');
        $this->testGuard->expects(static::once())
            ->method('canAccess')
            ->with(new stdClass(), new Context())
            ->willReturn(false);

        $this->instance->addGuard($this->testGuard);

        $this->expectException(AccessDeniedException::class);

        $this->instance->pipe(new stdClass(), new Context(), new Path(), null);
    }

    /** @test */
    public function itDoesNotCallGuardWhenPropertyNotRequired(): void
    {
        $transformer = $this
            ->getMockBuilder([CanTransform::class, HasAccessControl::class])
            ->getMock();
        $transformer->expects(static::once())
            ->method('getDefaultAcl')
            ->willReturn([]);
        $transformer->expects(static::once())
            ->method('getAclByProperty')
            ->willReturn([
                'optionalProperty' => ['test_guard'],
            ]);

        $this->transformerRepository->expects(static::once())
            ->method('getTransformer')
            ->willReturn($transformer);

        $this->testGuard->expects(static::once())
            ->method('getName')
            ->willReturn('test_guard');
        $this->testGuard->expects(static::never())
            ->method('canAccess');

        $this->instance->addGuard($this->testGuard);

        $this->instance->pipe(new stdClass(), new Context(), new Path(), null);
    }

    /** @test */
    public function itCallsGuardWhenPropertyRequired(): void
    {
        $context = new Context(['optionalProperty']);
        $transformer = $this
            ->getMockBuilder([CanTransform::class, HasAccessControl::class])
            ->getMock();
        $transformer->expects(static::once())
            ->method('getDefaultAcl')
            ->willReturn([]);
        $transformer->expects(static::once())
            ->method('getAclByProperty')
            ->willReturn([
                'optionalProperty' => ['test_guard'],
            ]);

        $this->transformerRepository->expects(static::once())
            ->method('getTransformer')
            ->willReturn($transformer);

        $this->testGuard->expects(static::once())
            ->method('getName')
            ->willReturn('test_guard');
        $this->testGuard->expects(static::once())
            ->method('canAccess')
            ->with(new stdClass(), $context)
            ->willReturn(true);

        $this->instance->addGuard($this->testGuard);

        $this->instance->pipe(new stdClass(), $context, new Path(), null);
    }
}
