<?php

declare(strict_types=1);

namespace FaustVik\Router\Validation;

use FaustVik\Router\interfaces\Validation\ParameterValidatorInterface;

class UuidValidator implements ParameterValidatorInterface
{
    private string $errorMessage = '';

    public function validate(string $value, array $options = []): bool
    {
        // UUID v4 pattern
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        if (!preg_match($pattern, $value)) {
            $this->errorMessage = "Value must be a valid UUID";
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
        return 'uuid';
    }
}
