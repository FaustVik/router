<?php

namespace FaustVik\Router\exceptions;

class NotFoundClass extends Exception
{
    public function __construct(string $class)
    {
        parent::__construct(sprintf("Not found class: %s", $class));
    }
}
