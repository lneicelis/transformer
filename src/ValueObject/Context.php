<?php

namespace Lneicelis\Transformer\ValueObject;

use Lneicelis\Transformer\Contract\HasSchema;

class Context implements HasSchema
{
    /** @var array */
    private $schema;

    public function __construct(array $schema = [])
    {
        $this->schema = $schema;
    }

    public function getSchema(): array
    {
        return $this->schema;
    }
}
