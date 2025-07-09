<?php

declare(strict_types=1);

namespace FaustVik\Router\Validation;

use FaustVik\Router\interfaces\Validation\ParameterValidatorInterface;

class RegexValidator implements ParameterValidatorInterface
{
    private string $errorMessage = '';

    public function validate(string $value, array $options = []): bool
    {
        if (!isset($options['pattern'])) {
            $this->errorMessage = "Regex pattern is required";
            return false;
        }

        $pattern = $options['pattern'];

        // Проверяем валидность регулярного выражения
        if (@preg_match($pattern, '') === false) {
            $this->errorMessage = "Invalid regex pattern";
            return false;
        }

        if (!preg_match($pattern, $value)) {
            $errorMessage = $options['message'] ?? "Value does not match the required pattern";
            $this->errorMessage = $errorMessage;
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
        return 'regex';
    }
}
