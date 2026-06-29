<?php

declare(strict_types=1);

namespace FaustVik\Router\Exceptions;

/**
 * Exception thrown when controller method is not found
 *
 * @package FaustVik\Router\exceptions
 */
final class NotFoundMethod extends Exception
{
    public function __construct(string $method, string $class)
    {
        parent::__construct(sprintf('Not found method: %s for class %s', $method, $class));
    }
}
