<?php

namespace Lneicelis\Transformer\Contract;

interface CanLoad
{
    public function getResourceClass(): string;

    public function getProperty(): string;

    public function load(array $ids): array;
}
