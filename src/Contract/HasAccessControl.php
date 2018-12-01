<?php

namespace Lneicelis\Transformer\Contract;

use Lneicelis\Transformer\ValueObject\AccessConfig;

interface HasAccessControl
{
    public function getAccessConfig(): AccessConfig;
}
