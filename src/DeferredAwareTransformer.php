<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use Lneicelis\Transformer\Helper\Arr;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Deferred;
use Lneicelis\Transformer\ValueObject\Path;

class DeferredAwareTransformer extends Transformer
{
    /** @var array[] */
    private $deferredTree = [];

    /** @var LoaderRegistry */
    private $loaderRegistry;

    public function __construct(array $pipes, LoaderRegistry $loaderRegistry)
    {
        parent::__construct($pipes);

        $this->loaderRegistry = $loaderRegistry;
    }

    /**
     * @param mixed $resource
     * @param Context|null $context
     * @return string|int|float|array
     * @throws TransformerNotFoundException
     */
    public function transform($resource, Context $context = null)
    {
        $context = $context ?: new Context();

        $result = parent::transform($resource, $context);
        $result = $this->loadDeferred($result, $context);

        return $result;
    }

    /**
     * @param $deferred
     * @param Context $context
     * @param Path $path
     * @return array|float|int|string
     * @throws TransformerNotFoundException
     */
    protected function transformAny($deferred, Context $context, Path $path)
    {
        if (! $deferred instanceof Deferred) {
            return parent::transformAny($deferred, $context, $path);
        }

        $resource = $deferred->getResource();
        $property = $deferred->getProperty();
        $resourceClass = get_class($resource);

        $this->deferredTree = Arr::setValue($this->deferredTree, [$resourceClass, $property, (string)$path], $deferred);

        return null;
    }

    /**
     * @param array $result
     * @param Context $context
     * @return array
     * @throws TransformerNotFoundException
     */
    protected function loadDeferred(array $result, Context $context): array
    {
        foreach ($this->deferredTree as $resourceClass => $properties) {
            unset($this->deferredTree[$resourceClass]);

            foreach ($properties as $property => $deferredByPath) {
                $loader = $this->loaderRegistry->getLoader($resourceClass, $property);
                $paths = array_keys($deferredByPath);
                $deferreds = array_values($deferredByPath);

                $loadedValues = $loader->load($deferreds);

                foreach ($paths as $index => $path) {
                    $segments = explode('.', $path);
                    $path = new Path($segments);
                    $value = $this->transformAny($loadedValues[$index], $context, $path);

                    $result = Arr::setValue($result, $segments, $value);
                }
            }
        }

        if (! empty($this->deferredTree)) {
            return $this->loadDeferred($result, $context);
        }

        return $result;
    }
}
