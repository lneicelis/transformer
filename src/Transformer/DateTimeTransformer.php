<?php

namespace Lneicelis\Transformer\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use DateTime;

class DateTimeTransformer implements CanTransform
{
    public function getResourceClass(): string
    {
        return DateTime::class;
    }

    /**
     * @param DateTime $resource
     * @return string|int|float|array
     */
    public function transform($resource)
    {
        return $resource->format(DateTime::ISO8601);
    }
}
