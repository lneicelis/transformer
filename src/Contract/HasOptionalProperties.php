<?php

namespace Lneicelis\Transformer\Contract;

interface HasOptionalProperties extends CanTransform
{
    /**
     * @param mixed $source any php object that has public properties or methods
     * @return array
     */
    public function transform($source): array;
}

