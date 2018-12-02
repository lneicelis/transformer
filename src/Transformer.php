<?php

namespace Lneicelis\Transformer;

use Lneicelis\Transformer\Contract\CanPipe;
use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Exception\TransformerNotFoundException;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Path;

class Transformer
{
    /** @var CanPipe[] */
    protected $pipes;

    /**
     * @param CanPipe[] $pipes
     */
    public function __construct(array $pipes = [])
    {
        $this->pipes = $pipes;
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

        return $this->transformAny($resource, $context, new Path());
    }

    /**
     * @param $resource
     * @param Context $context
     * @param Path $path
     * @return array|float|int|string
     * @throws TransformerNotFoundException
     */
    protected function transformAny($resource, Context $context, Path $path)
    {
        switch (true) {
            case is_object($resource):
                return $this->transformObject($resource, $context, $path);
            case is_array($resource):
                return $this->transformArray($resource, $context, $path);
            default:
                return $resource;
        }
    }

    /**
     * @param object $resource
     * @param Context $context
     * @param Path $path
     * @return string|int|float|array
     * @throws TransformerNotFoundException
     */
    protected function transformObject($resource, Context $context, Path $path)
    {
        $result = $this->pipe($resource, $context, $path, null);

        return $this->transformAny($result, $context, $path);
    }

    /**
     * @param array $resourceByKey
     * @param Context $context
     * @param Path $path
     * @return array
     * @throws TransformerNotFoundException
     */
    protected function transformArray(array $resourceByKey, Context $context, Path $path)
    {
        $dataByKey = [];

        foreach ($resourceByKey as $key => $resource) {
            $dataByKey[$key] = $this->transformAny($resource, $context, $path->stepIn($key));
        }

        return $dataByKey;
    }

    /**
     * @param object $resource
     * @param Context $context
     * @param Path $path
     * @param string|int|float|array $data
     * @return string|int|float|array
     */
    protected function pipe($resource, Context $context, Path $path, $data)
    {
        return array_reduce(
            $this->pipes,
            function ($data, CanPipe $middleware) use ($resource, $context, $path) {
                return $middleware->pipe($resource, $context, $path, $data);
            },
            $data
        );
    }
}
