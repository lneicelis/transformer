<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Contract\HasLazyProperties;
use Lneicelis\Transformer\Pipe\LazyPropertiesPipe;
use Lneicelis\Transformer\Pipe\TransformPipe;
use Lneicelis\Transformer\ValueObject\Context;
use DateTimeZone;
use DateTime;
use PHPUnit\Framework\TestCase;
use stdClass;

class TransformerTest extends TestCase
{
    /** @var TransformerRegistry */
    private $transformerRegistry;

    /** @var TransformPipe */
    private $transformPipe;

    /** @var LazyPropertiesPipe */
    private $lazyPropertiesPipe;

    /** @var Transformer */
    private $instance;

    protected function setUp()
    {
        parent::setUp();

        $this->transformerRegistry = new TransformerRegistry();
        $this->transformPipe = new TransformPipe($this->transformerRegistry);
        $this->lazyPropertiesPipe = new LazyPropertiesPipe($this->transformerRegistry);
        $this->instance = new Transformer([
            $this->transformPipe,
            $this->lazyPropertiesPipe,
        ]);
    }

    /**
     * @test
     * @dataProvider scalarsData
     */
    public function itLeavesUnchanged($input, $expectedOuput): void
    {
        $context = new Context();
        $output = $this->instance->transform($input, $context);

        $this->assertEquals($output, $expectedOuput);
    }

    public function scalarsData(): array
    {

        return [
            [
                'input' => null,
                'output' => null,
            ],
            [
                'input' => 'test',
                'output' => 'test',
            ],
            [
                'input' => 123,
                'output' => 123,
            ],
            [
                'input' => 123,
                'output' => 123,
            ],
            [
                'input' => [null],
                'output' => [null],
            ],

        ];
    }

    /** @test */
    public function itTransformsObject(): void
    {
        $this->transformerRegistry->addTransformer(new Transformer\DateTimeTransformer());

        $resource = new DateTime('2000-01-01 12:00:00');
        $context = new Context();
        $result = $this->instance->transform($resource, $context);

        $this->assertEquals('2000-01-01T12:00:00+0000', $result);
    }

    /** @test */
    public function itTransformsArray(): void
    {
        $this->transformerRegistry->addTransformer(new Transformer\DateTimeTransformer());

        $resource = [
            'createdAt' => new DateTime('2000-01-01 12:00:00'),
        ];
        $context = new Context();
        $result = $this->instance->transform($resource, $context);
        $expected = [
            'createdAt' => '2000-01-01T12:00:00+0000',
        ];

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function itTransformsRecursively(): void
    {
        $transformer = new class implements CanTransform {
            function getResourceClass(): string {
                return DateTimeZone::class;
            }

            function transform($resource) {
                return [
                    'type' => 'dateInterval',
                    'createdAt' => new DateTime('2000-01-01 12:00:00'),
                ];
            }
        };

        $this->transformerRegistry->addTransformer($transformer);
        $this->transformerRegistry->addTransformer(new Transformer\DateTimeTransformer());

        $resource = new DateTimeZone('+2000');
        $context = new Context();
        $data = $this->instance->transform($resource, $context);
        $expectedData = [
            'type' => 'dateInterval',
            'createdAt' => '2000-01-01T12:00:00+0000',
        ];

        $this->assertEquals($expectedData, $data);
    }

    /** @test */
    public function itTransformsUsingInheritance(): void
    {
        $this->transformerRegistry->addTransformer(new Transformer\DateTimeTransformer());

        $childResource = new class('2000-01-01 12:00:00') extends DateTime {};

        $context = new Context();
        $result = $this->instance->transform($childResource, $context);

        $this->assertEquals('2000-01-01T12:00:00+0000', $result);
    }

    /** @test */
    public function itAddsOptionalProperties(): void
    {
        $transformer = new class implements CanTransform, HasLazyProperties {
            function getResourceClass(): string {
                return DateTimeZone::class;
            }

            function transform($resource): array {
                return [
                    'type' => 'dateInterval',
                ];
            }

            function timeZone(DateTimeZone $resource): string {
                return $resource->getName();
            }
        };

        $this->transformerRegistry->addTransformer($transformer);

        $resource = new DateTimeZone('+2000');
        $context = new Context(['timeZone']);

        $data = $this->instance->transform($resource, $context);
        $expectedData = [
            'type' => 'dateInterval',
            'timeZone' => '+20:00',
        ];

        $this->assertEquals($expectedData, $data);
    }

    /** @test */
    public function itRecurse(): void
    {
        $this->transformerRegistry->addTransformer(
            new class implements CanTransform, HasLazyProperties {
                function getResourceClass(): string {
                    return stdClass::class;
                }

                function transform($resource): array {
                    return [
                        'id' => $resource->id,
                    ];
                }

                function resource(stdClass $resource): stdClass {
                    return $resource;
                }
            }
        );

        $resource = new stdClass();
        $resource->id = 123;
        $context = new Context([
            'resource' => [
                'resource',
            ],
        ]);

        $data = $this->instance->transform($resource, $context);
        $expectedData = [
            'id' => 123,
            'resource' => [
                'id' => 123,
                'resource' => [
                    'id' => 123,
                ],
            ],
        ];

        $this->assertEquals($expectedData, $data);
    }
}
