<?php

namespace FaustVik\Router\exceptions;

class NoMatch extends Exception
{
    public function __construct(string $uri)
    {
        parent::__construct(sprintf("Not found route for uri: %s", $uri));
    }
}
