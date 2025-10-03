<?php

declare(strict_types=1);

namespace FaustVik\Router\exceptions;

final class ValidationException extends Exception
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
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorsAsString(): string
    {
        return implode(', ', $this->errors);
    }
}
