<?php

declare(strict_types=1);

namespace FaustVik\Router\Validation;

use FaustVik\Router\interfaces\Validation\ParameterValidatorInterface;

class EmailValidator implements ParameterValidatorInterface
{
    private string $errorMessage = '';

    public function validate(string $value, array $options = []): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errorMessage = "Value must be a valid email address";
            return false;
        }

        return true;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getName(): string
    {
        return 'email';
    }
} 