<?php

namespace Lneicelis\Transformer\ValueObject;

class NullProxy extends ArrayProxy
{
    static $instance;

    public static function instance(): self
    {
        return self::$instance = self::$instance ?: new static([]);
    }
}
