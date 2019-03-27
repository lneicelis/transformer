<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Exception\DuplicateResourceTransformerException;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;

class TransformerRegistry
{
    /** @var CanTransform[] */
    protected $transformerByResourceClass = [];

    /**
     * @param CanTransform $transformer
     * @param bool $override
     * @return $this
     * @throws DuplicateResourceTransformerException
     */
    public function addTransformer(CanTransform $transformer, bool $override = false)
    {
        $resourceClass = $transformer->getResourceClass();

        if ($override === false && array_key_exists($resourceClass, $this->transformerByResourceClass)) {
            throw new DuplicateResourceTransformerException(sprintf(
                'Transformer for resource "%s" already registered in "%s"',
                $resourceClass,
                get_class($this->transformerByResourceClass[$resourceClass])
            ));
        }

        $this->transformerByResourceClass[$resourceClass] = $transformer;

        return $this;
    }

    /**
     * @param mixed $resource
     * @return CanTransform
     * @throws TransformerNotFoundException
     */
    public function getTransformer($resource): CanTransform
    {
        $className = get_class($resource);

        do {
            if (isset($this->transformerByResourceClass[$className])) {
                return $this->transformerByResourceClass[$className];
            }
        } while ($className = get_parent_class($className));

        throw new TransformerNotFoundException(
            sprintf('Transformer for resource "%s" is missing', get_class($resource))
        );
    }
}
