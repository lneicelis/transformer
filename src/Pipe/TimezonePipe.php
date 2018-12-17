<?php

namespace Lneicelis\Transformer\Pipe;

use DateTime;
use Lneicelis\Transformer\Contract\CanPipe;
use Lneicelis\Transformer\Contract\HasTimezone;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Path;

class TimezonePipe implements CanPipe
{
    /**
     * @param object $resource
     * @param Context $context
     * @param Path $path
     * @param $data
     * @return mixed
     */
    public function pipe($resource, Context $context, Path $path, $data)
    {
        if (! $context instanceof HasTimezone) {
            return $data;
        }

        if (! $resource instanceof DateTime) {
            return $data;
        }

        $resource->setTimezone($context->getTimezone());

        return $data;
    }
}
