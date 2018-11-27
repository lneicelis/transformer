<?php

namespace Lneicelis\Transformer\ValueObject;

class Path
{
    /** @var string[] */
    private $segments;

    public function __construct(array $segments = [])
    {
        $this->segments = $segments;
    }

    public function isInRoot(): bool
    {
        return empty($this->segments);
    }

    public function stepIn(string $segment): Path
    {
        return new Path(array_merge($this->segments, [$segment]));
    }

    public function back(): Path
    {
        return new Path(array_slice($this->segments, 0, count($this->segments) - 1));
    }

    public function getSegments(): array
    {
        return $this->segments;
    }
}
