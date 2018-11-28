<?php

namespace Lneicelis\Transformer\Contract;

use Lneicelis\Transformer\ValueObject\Context;

interface CanGuard
{
    public function getName(): string;

    public function canAccess($source, Context $context): bool;
}
