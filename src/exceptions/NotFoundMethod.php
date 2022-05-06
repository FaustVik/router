<?php

namespace FaustVik\Router\exceptions;

class NotFoundMethod extends Exception
{
    public function __construct(string $method, string $class)
    {
        parent::__construct(sprintf("Not found method: %s for class %s", $method, $class));
    }
}
