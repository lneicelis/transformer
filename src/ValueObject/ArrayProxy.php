<?php

namespace Lneicelis\Transformer\ValueObject;

use ArrayAccess;
use IteratorAggregate;

abstract class ArrayProxy implements ArrayAccess, IteratorAggregate
{
    /** @var mixed */
    private $resource;

    /**
     * @param mixed $resource
     */
    public function __construct(array $resource)
    {
        $this->resource = $resource;
    }

    public static function map(array $resources)
    {
        return array_map(function (array $resource) {
            return new static($resource);
        }, $resources);
    }

    public function __get(string $property)
    {
        return $this->get($property);
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {

    }

    public function offsetUnset($offset)
    {

    }

    public function getIterator()
    {
        return $this->resource;
    }

    private function get($property)
    {
        if (array_key_exists($this->resource, $this->resource)) {
            $this->resource[$property];
        }

        return NullProxy::instance();
    }
}
