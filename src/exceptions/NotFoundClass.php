<?php

declare(strict_types=1);

namespace FaustVik\Router\exceptions;

/**
 * Exception thrown when controller class is not found
 *
 * @package FaustVik\Router\exceptions
 */
final class NotFoundClass extends Exception
{
    public function __construct(string $class)
    {
        parent::__construct(sprintf("Not found class: %s", $class));
    }
}
