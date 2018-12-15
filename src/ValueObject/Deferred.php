<?php

namespace Lneicelis\Transformer\ValueObject;

class Deferred
{
    /** @var string */
    private $resourceClass;

    /** @var string */
    private $id;

    public function __construct(string $resourceClass, string $id)
    {
        $this->resourceClass = $resourceClass;
        $this->id = $id;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
