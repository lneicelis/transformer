<?php

namespace Lneicelis\Transformer\ValueObject;

class AccessConfig
{
    /** @var string[] */
    private $defaultGroups = [];

    /** @var array[] */
    private $groupsByProperty = [];

    public function __construct(array $defaultGroups = [], array $groupsByProperty = [])
    {
        $this->defaultGroups = $defaultGroups;
        $this->groupsByProperty = $groupsByProperty;
    }

    public function setDefault(array $groups): AccessConfig
    {
        $this->defaultGroups = $groups;

        return $this;
    }

    public function set(string $property, array $groups): AccessConfig
    {
        $this->groupsByProperty[$property] = $groups;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getDefaultGroups(): array
    {
        return $this->defaultGroups;
    }

    /**
     * @return array[]
     */
    public function getGroupsByProperty(): array
    {
        return $this->groupsByProperty;
    }
}
