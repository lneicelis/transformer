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
     * @param $data
     * @param Path $path
     * @throws TransformationException
     * @return mixed
     */
    public function pipe($source, Context $context, Path $path, $data);
}
