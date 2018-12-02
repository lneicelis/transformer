<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use Lneicelis\Transformer\Pipe\LazyPropertiesPipe;
use PHPUnit\Framework\TestCase;

class TransformerUnitTestCase extends TestCase
{
    /** @var TransformerRegistry */
    private $transformerRegistry;

    /** @var LazyPropertiesPipe */
    private $lazyPropertiesPipe;

    /** @var Transformer */
    private $transformer;

    protected function setUp()
    {
        parent::setUp();

        $this->transformerRegistry = $this->createMock(TransformerRegistry::class);

        $this->transformerRegistry = new class extends TransformerRegistry {
            function getTransformer($resource): CanTransform
            {
                try {
                    return parent::getTransformer($resource);
                } catch (TransformerNotFoundException $exception) {

                }
            }
        };
        $this->lazyPropertiesPipe = new LazyPropertiesPipe($this->transformerRegistry);
        $this->transformer = new Transformer([
            $this->lazyPropertiesPipe,
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
