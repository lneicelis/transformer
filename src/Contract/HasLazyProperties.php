<?php

namespace Lneicelis\Transformer\Contract;

interface HasLazyProperties extends CanTransform
{
    /**
     * @param mixed $resource any php object that has public properties or methods
     * @return array
     */
    public function transform($resource): array;
}

