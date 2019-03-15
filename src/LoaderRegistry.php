<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanLoad;
use Lneicelis\Transformer\Helper\Arr;

class LoaderRegistry
{
    /** @var array[] */
    private $loaderTree = [];

    public function addLoader(CanLoad $loader): void
    {
        $resourceClass = $loader->getResourceClass();
        $resourceProperty = $loader->getProperty();

        $this->loaderTree = Arr::setValue($this->loaderTree, [$resourceClass, $resourceProperty], $loader);
    }

    public function getLoader(string $resourceClass, string $resourceProperty): CanLoad
    {
        return $this->loaderTree[$resourceClass][$resourceProperty];
    }

    public function getLoadableProperties(string $resourceClass): array
    {
        $loaderByProperty = $this->loaderTree[$resourceClass];

        return array_keys($loaderByProperty);
    }
}
