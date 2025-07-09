<?php

declare(strict_types=1);

namespace FaustVik\Router\exceptions;

use Exception;

class ValidationException extends Exception
{
    private string $parameter;
    private string $value;
    private array $errors;

    public function __construct(string $parameter, string $value, array $errors, int $code = 400)
    {
        $this->parameter = $parameter;
        $this->value = $value;
        $this->errors = $errors;

        $message = "Validation failed for parameter '{$parameter}' with value '{$value}': " . implode(', ', $errors);
        parent::__construct($message, $code);
    }//end __construct()

    public function getParameter(): string
    {
        return $this->parameter;
    }//end getParameter()

    public function getValue(): string
    {
        return $this->value;
    }//end getValue()

    public function getErrors(): array
    {
        return $this->errors;
    }//end getErrors()

    public function getErrorsAsString(): string
    {
        return implode(', ', $this->errors);
    }//end getErrorsAsString()
}//end class
