<?php

declare(strict_types=1);

namespace FaustVik\Router\Exceptions;

/**
 * Exception thrown when no route matches the URI
 *
 * @package FaustVik\Router\exceptions
 */
final class NoMatch extends Exception
{
    public function __construct(string $uri)
    {
        parent::__construct(sprintf('Not found route for uri: %s', $uri));
    }
}
