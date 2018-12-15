<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanLoad;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use Lneicelis\Transformer\Helper\Arr;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Deferred;
use Lneicelis\Transformer\ValueObject\Path;

class DeferredAwareTransformer extends Transformer
{
    /** @var array[] */
    private $deferredTree = [];

    /** @var CanLoad */
    private $loaderByResourceClass;

    public function addLoader(CanLoad $loader): void
    {
        $this->loaderByResourceClass[$loader->getResourceName()] = $loader;
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
        if (! empty($this->deferredTree)) {
            $result = $this->loadDeferred($result);

            return $this->transform($result, $context);
        }

        return $result;
    }

    /**
     * @param $resource
     * @param Context $context
     * @param Path $path
     * @return array|float|int|string
     * @throws TransformerNotFoundException
     * @throws Exception\TransformerNotFoundException
     */
    protected function transformAny($resource, Context $context, Path $path)
    {
        if (! $resource instanceof Deferred) {
            return parent::transformAny($resource, $context, $path);
        }

        $resourceClass = $resource->getResourceClass();
        $resourceId = $resource->getId();
        $this->deferredTree[$resourceClass][$resourceId] = $path;

        return null;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function loadDeferred(array $result): array
    {
        foreach ($this->deferredTree as $resourceClass => $pathByResourceId) {
            $loader = $this->getLoader($resourceClass);
            $ids = array_keys($pathByResourceId);

            $loadedValues = $loader->load($ids);

            foreach (array_values($pathByResourceId) as $index => $path) {
                /** @var Path $path */
                $result = Arr::setValue(
                    $result,
                    $path->getSegments(),
                    $loadedValues[$index]
                );
            }

            unset($this->deferredTree[$resourceClass]);
        }

        return $result;
    }

    protected function getLoader(string $resourceClass): CanLoad
    {
        return $this->loaderByResourceClass[$resourceClass];
    }
}
