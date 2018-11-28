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
     * @param mixed $source
     * @param Context $context
     * @return string|int|float|array
     * @throws TransformerNotFoundException
     */
    public function transform($source, Context $context)
    {
        return $this->transformAny($source, $context, new Path());
    }

    /**
     * @param $source
     * @param Context $context
     * @param Path $path
     * @return array|float|int|string
     * @throws TransformerNotFoundException
     */
    protected function transformAny($source, Context $context, Path $path)
    {
        switch (true) {
            case is_object($source):
                return $this->transformObject($source, $context, $path);
            case is_array($source):
                return $this->transformArray($source, $context, $path);
            default:
                return $source;
        }
    }

    /**
     * @param object $source
     * @param Context $context
     * @param Path $path
     * @return string|int|float|array
     * @throws TransformerNotFoundException
     */
    protected function transformObject($source, Context $context, Path $path)
    {
        $result = $this->pipe($source, $context, $path, null);

        return $this->transformAny($result, $context, $path);
    }

    /**
     * @param array $sourceByKey
     * @param Context $context
     * @param Path $path
     * @return array
     * @throws TransformerNotFoundException
     */
    protected function transformArray(array $sourceByKey, Context $context, Path $path)
    {
        $dataByKey = [];

        foreach ($sourceByKey as $key => $source) {
            $dataByKey[$key] = $this->transformAny($source, $context, $path->stepIn($key));
        }

        return $dataByKey;
    }

    /**
     * @param object $source
     * @param Context $context
     * @param Path $path
     * @param string|int|float|array $data
     * @return string|int|float|array
     */
    protected function pipe($source, Context $context, Path $path, $data)
    {
        return array_reduce(
            $this->pipes,
            function ($data, CanPipe $middleware) use ($source, $context, $path) {
                return $middleware->pipe($source, $context, $path, $data);
            },
            $data
        );
    }
}
