<?php

namespace Lneicelis\Transformer\Contract;

interface HasAccessControl
{
    /**
     * @return string[]
     */
    public function getDefaultAcl(): array;

    /**
     * @return array[]
     */
    public function getAclByProperty(): array;
}
