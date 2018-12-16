<?php

namespace Lneicelis\Transformer\Transformer;

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\ValueObject\NullProxy;

class NullProxyTransformer implements CanTransform
{
    public function getResourceClass(): string
    {
        return NullProxy::class;
    }

    /**
     * @param NullProxy $resource
     * @return null
     */
    public function transform($resource)
    {
        return null;
    }
}
