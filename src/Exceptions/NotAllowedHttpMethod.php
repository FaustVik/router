<?php

declare(strict_types=1);

namespace FaustVik\Router\Exceptions;

/**
 * Exception thrown when HTTP method is not allowed for route
 *
 * @package FaustVik\Router\exceptions
 */
final class NotAllowedHttpMethod extends Exception
{
    public function __construct(string $method)
    {
        parent::__construct(sprintf('Not allowed http method: %s', $method));
    }
}
