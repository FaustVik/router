<?php

namespace FaustVik\Router\exceptions;

class NotAllowedHttpMethod extends Exception
{
    public function __construct(string $method)
    {
        parent::__construct(sprintf("Not allowed http method: %s", $method));
    }//end __construct()
}//end class
