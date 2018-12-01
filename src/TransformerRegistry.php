<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Exception\DuplicateResourceTransformerException;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;

class TransformerRegistry
{
    /** @var CanTransform[] */
    protected $transformerBySourceClass = [];

    /**
     * @param CanTransform $transformer
     * @return $this
     * @throws DuplicateResourceTransformerException
     */
    public function addTransformer(CanTransform $transformer)
    {
        $sourceClass = $transformer->getSourceClass();

        if (array_key_exists($sourceClass, $this->transformerBySourceClass)) {
            throw new DuplicateResourceTransformerException(sprintf(
                'Transformer for source "%s" already registered in "%s"',
                $sourceClass,
                get_class($this->transformerBySourceClass[$sourceClass])
            ));
        }

        $this->transformerBySourceClass[$sourceClass] = $transformer;

        return $this;
    }

    /**
     * @param mixed $source
     * @return CanTransform
     * @throws TransformerNotFoundException
     */
    public function getTransformer($source): CanTransform
    {
        $className = get_class($source);

        do {
            if (isset($this->transformerBySourceClass[$className])) {
                return $this->transformerBySourceClass[$className];
            }
        } while ($className = get_parent_class($className));

        throw new TransformerNotFoundException(
            sprintf('Transformer for source "%s" is missing', get_class($source))
        );
    }
}
