<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use Lneicelis\Transformer\Pipe\OptionalPropertiesPipe;
use PHPUnit\Framework\TestCase;

class TransformerUnitTestCase extends TestCase
{
    /** @var TransformerRepository */
    private $transformerRepository;

    /** @var OptionalPropertiesPipe */
    private $optionalPropsPipe;

    /** @var Transformer */
    private $transformer;

    protected function setUp()
    {
        parent::setUp();

        $this->transformerRepository = $this->createMock(TransformerRepository::class);

        $this->transformerRepository = new class extends TransformerRepository {
            function getTransformer($source): CanTransform
            {
                try {
                    return parent::getTransformer($source);
                } catch (TransformerNotFoundException $exception) {

                }
            }
        };
        $this->optionalPropsPipe = new OptionalPropertiesPipe($this->transformerRepository);
        $this->transformer = new Transformer($this->transformerRepository, [
            $this->optionalPropsPipe,
        ]);
    }

    /**
     * @throws Exception\DuplicateResourceTransformerException
     */
    protected function setTransformer(CanTransform $transformer): void
    {
        $this->transformer->addTransformer($transformer);
    }

}
