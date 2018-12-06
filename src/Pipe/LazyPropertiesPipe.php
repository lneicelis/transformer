<?php

namespace Lneicelis\Transformer\Pipe;

use Lneicelis\Transformer\Contract\CanPipe;
use Lneicelis\Transformer\Contract\HasLazyProperties;
use Lneicelis\Transformer\Contract\HasSchema;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use Lneicelis\Transformer\TransformerRegistry;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Path;

class LazyPropertiesPipe implements CanPipe
{
    /** @var TransformerRegistry */
    protected $transformerRepository;

    /**
     * @param TransformerRegistry $transformerRepository
     */
    public function __construct(TransformerRegistry $transformerRepository)
    {
        $this->transformerRepository = $transformerRepository;
    }

    /**
     * @param object $resource
     * @param Context $context
     * @param Path $path
     * @param $data
     * @return array
     * @throws TransformerNotFoundException
     */
    public function pipe($resource, Context $context, Path $path, $data)
    {
        if (! $context instanceof HasSchema) {
            return $data;
        }

        $transformer = $this->transformerRepository->getTransformer($resource);

        if (! $transformer instanceof HasLazyProperties) {
            return $data;
        }


        $schema = $this->getPathSchema($path, $context->getSchema());
        $properties = $this->getProperties($schema);

        return array_reduce(
            $properties,
            function (array $data, string $key) use ($resource, $transformer) {
                if (! array_key_exists($key, $data)) {
                    $data[$key] = $transformer->{$key}($resource);
                }

                return $data;
            },
            $data
        );
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
