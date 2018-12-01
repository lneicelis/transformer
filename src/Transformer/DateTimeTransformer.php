<?php

namespace Lneicelis\Transformer\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use DateTime;

class DateTimeTransformer implements CanTransform
{
    public static function getSourceClass(): string
    {
        return DateTime::class;
    }

    /**
     * @param DateTime $source
     * @return string|int|float|array
     */
    public function transform($source)
    {
        return $source->format(DateTime::ISO8601);
    }
}
