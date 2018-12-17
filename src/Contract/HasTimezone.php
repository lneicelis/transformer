<?php

namespace Lneicelis\Transformer\Contract;

use DateTimeZone;

interface HasTimezone
{
    public function getTimezone(): DateTimeZone;
}
