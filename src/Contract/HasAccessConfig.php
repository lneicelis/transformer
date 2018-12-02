<?php

namespace Lneicelis\Transformer\Contract;

use Lneicelis\Transformer\ValueObject\AccessConfig;

interface HasAccessConfig
{
    public function getAccessConfig(): AccessConfig;
}
