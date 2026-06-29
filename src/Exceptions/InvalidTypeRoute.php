<?php

declare(strict_types=1);

namespace FaustVik\Router\Exceptions;

/**
 * Exception thrown when route type is invalid
 *
 * @package FaustVik\Router\exceptions
 */
final class InvalidTypeRoute extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid type route');
    }
}
