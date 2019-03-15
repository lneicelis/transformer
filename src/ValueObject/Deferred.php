<?php

namespace Lneicelis\Transformer\ValueObject;

class Deferred
{
    /** @var object */
    private $resource;

    /** @var string */
    private $property;

    public function __construct($resource, string $property)
    {
        $this->resource = $resource;
        $this->property = $property;
    }

    /**
     * @return object
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return $this->property;
    }
}
