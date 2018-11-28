<?php

namespace Lneicelis\Transformer\Contract;

use Lneicelis\Transformer\Exception\TransformationException;
use Lneicelis\Transformer\ValueObject\Context;
use Lneicelis\Transformer\ValueObject\Path;

interface CanPipe
{
    /**
     * @param object $source
     * @param Context $context
     * @param Path $path
     * @param $data
     * @throws TransformationException
     * @return mixed
     */
    public function pipe($source, Context $context, Path $path, $data);
}
