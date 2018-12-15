<?php

namespace Lneicelis\Transformer\Contract;

interface CanLoad
{
    public function getResourceName(): string;

    public function load(array $ids): array;
}
