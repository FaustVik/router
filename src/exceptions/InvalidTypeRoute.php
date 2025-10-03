<?php

declare(strict_types=1);

namespace FaustVik\Router\exceptions;

class InvalidTypeRoute extends Exception
{
    public function __construct()
    {
        parent::__construct("Invalid type route");
    }
}
