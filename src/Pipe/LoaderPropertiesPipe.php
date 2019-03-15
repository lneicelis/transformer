<?php

namespace Lneicelis\Transformer\Pipe;

use Lneicelis\Transformer\Contract\CanPipe;
use Lneicelis\Transformer\Contract\HasLazyProperties;
use Lneicelis\Transformer\Contract\HasSchema;
use Lneicelis\Transformer\LoaderRegistry;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Deferred;
use Lneicelis\Transformer\ValueObject\Path;

class LoaderPropertiesPipe implements CanPipe
{
    /** @var LoaderRegistry */
    protected $loaderRegistry;

    /**
     * @param LoaderRegistry $loaderRegistry
     */
    public function __construct(LoaderRegistry $loaderRegistry)
    {
        $this->loaderRegistry = $loaderRegistry;
    }

    /**
     * @param object $resource
     * @param Context $context
     * @param Path $path
     * @param $data
     * @return array
     */
    public function pipe($resource, Context $context, Path $path, $data)
    {
        if (! $context instanceof HasSchema) {
            return $data;
        }

        $loadableProperties = $this->loaderRegistry->getLoadableProperties(get_class($resource));
        $schema = $this->getPathSchema($path, $context->getSchema());
        $schemaProperties = $this->getProperties($schema);
        $properties = array_intersect($schemaProperties, $loadableProperties);

        foreach ($properties as $property) {
            $data[$property] = new Deferred($resource, $property);
        }

        return $data;
    }

    private function getProperties(array $schema): array
    {
        return array_map(function ($value, $key) {
            return is_string($value) ? $value : $key;
        }, $schema, array_keys($schema));
    }

    private function getPathSchema(Path $path, array $schema): array
    {
        $segments = array_filter($path->getSegments(), function ($segment) {
            return ! is_numeric($segment);
        });

        return $this->arrayGet($schema, $segments, []);
    }

    private function arrayGet(array $array, array $path, $default): array
    {
        foreach ($path as $key) {
            if (! array_key_exists($key, $array)) {
                return $default;
            }

            $array = $array[$key];
        }

        return $array;
    }
}
