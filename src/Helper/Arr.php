<?php

namespace Lneicelis\Transformer\Helper;

class Arr
{
    public static function setValue(array $array, array $path, $value): array
    {
        $lastKey = array_pop($path);
        $current = &$array;

        foreach($path as $key) {
            $current = &$current[$key];
        }

        $current[$lastKey] = $value;

        return $array;
    }
}
