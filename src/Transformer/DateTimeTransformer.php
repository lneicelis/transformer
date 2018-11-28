<?php

namespace Lneicelis\Transformer\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use DateTime;
use Lneicelis\Transformer\Contract\HasAccessControl;

class DateTimeTransformer implements CanTransform, HasAccessControl
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

    /**
     * @return string[]
     */
    public function getDefaultAcl(): array
    {
        return [];
    }

    /**
     * @return array[]
     */
    public function getPropertyAcl(string $property): array
    {
        return [
            'id' => ['owner'],
        ];
    }
}
