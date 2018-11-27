<?php

namespace Lneicelis\Transformer\Contract;

interface CanTransform
{
    /**
     * @return string
     */
    public static function getSourceClass(): string;

    /**
     * @param mixed $source any php object that has public properties or methods
     * @return string|int|float|array
     */
    public function transform($source);
}

