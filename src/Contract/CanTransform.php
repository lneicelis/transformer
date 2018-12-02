<?php

namespace Lneicelis\Transformer\Contract;

interface CanTransform
{
    /**
     * @return string
     */
    public function getResourceClass(): string;

    /**
     * @param mixed $resource any php object that has public properties or methods
     * @return string|int|float|array
     */
    public function transform($resource);
}

