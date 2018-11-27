<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Contract\HasOptionalProperties;
use Lneicelis\Transformer\Pipe\OptionalPropertiesPipe;
use Lneicelis\Transformer\ValueObject\Context;
use DateTimeZone;
use DateTime;
use PHPUnit\Framework\TestCase;
use stdClass;

class TransformerTest extends TestCase
{
    /** @var TransformerRepository */
    private $transformerRepository;

    /** @var OptionalPropertiesPipe */
    private $optionalPropsPipe;

    /** @var Transformer */
    private $instance;

    protected function setUp()
    {
        parent::setUp();

        $this->transformerRepository = new TransformerRepository();
        $this->optionalPropsPipe = new OptionalPropertiesPipe($this->transformerRepository);
        $this->instance = new Transformer($this->transformerRepository, [
            $this->optionalPropsPipe,
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
        $this->instance->addTransformer(new Transformer\DateTimeTransformer());

        $resource = new DateTime('2000-01-01 12:00:00');
        $context = new Context();
        $result = $this->instance->transform($resource, $context);

        $this->assertEquals('2000-01-01T12:00:00+0000', $result);
    }

    /** @test */
    public function itTransformsArray(): void
    {
        $this->instance->addTransformer(new Transformer\DateTimeTransformer());

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
            static function getSourceClass(): string {
                return DateTimeZone::class;
            }

            function transform($source) {
                return [
                    'type' => 'dateInterval',
                    'createdAt' => new DateTime('2000-01-01 12:00:00'),
                ];
            }
        };

        $this->instance->addTransformer($transformer);
        $this->instance->addTransformer(new Transformer\DateTimeTransformer());

        $source = new DateTimeZone('+2000');
        $context = new Context();
        $data = $this->instance->transform($source, $context);
        $expectedData = [
            'type' => 'dateInterval',
            'createdAt' => '2000-01-01T12:00:00+0000',
        ];

        $this->assertEquals($expectedData, $data);
    }

    /** @test */
    public function itTransformsUsingInheritance(): void
    {
        $this->instance->addTransformer(new Transformer\DateTimeTransformer());

        $childResource = new class('2000-01-01 12:00:00') extends DateTime {};

        $context = new Context();
        $result = $this->instance->transform($childResource, $context);

        $this->assertEquals('2000-01-01T12:00:00+0000', $result);
    }

    /** @test */
    public function itAddsOptionalProperties(): void
    {
        $transformer = new class implements CanTransform, HasOptionalProperties {
            static function getSourceClass(): string {
                return DateTimeZone::class;
            }

            function transform($source): array {
                return [
                    'type' => 'dateInterval',
                ];
            }

            function timeZone(DateTimeZone $source): string {
                return $source->getName();
            }
        };

        $this->instance->addTransformer($transformer);

        $source = new DateTimeZone('+2000');
        $context = new Context(['timeZone']);

        $data = $this->instance->transform($source, $context);
        $expectedData = [
            'type' => 'dateInterval',
            'timeZone' => '+20:00',
        ];

        $this->assertEquals($expectedData, $data);
    }

    /** @test */
    public function itRecurse(): void
    {
        $this->instance->addTransformer(
            new class implements CanTransform, HasOptionalProperties {
                static function getSourceClass(): string {
                    return stdClass::class;
                }

                function transform($source): array {
                    return [
                        'id' => $source->id,
                    ];
                }

                function source(stdClass $source): stdClass {
                    return $source;
                }
            }
        );

        $source = new stdClass();
        $source->id = 123;
        $context = new Context([
            'source' => [
                'source',
            ],
        ]);

        $data = $this->instance->transform($source, $context);
        $expectedData = [
            'id' => 123,
            'source' => [
                'id' => 123,
                'source' => [
                    'id' => 123,
                ],
            ],
        ];

        $this->assertEquals($expectedData, $data);
    }
}