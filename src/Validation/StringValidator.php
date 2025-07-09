<?php

declare(strict_types=1);

namespace FaustVik\Router\Validation;

use FaustVik\Router\interfaces\Validation\ParameterValidatorInterface;

class StringValidator implements ParameterValidatorInterface
{
    private string $errorMessage = '';

    public function validate(string $value, array $options = []): bool
    {
        // Проверяем минимальную длину
        if (isset($options['min_length']) && strlen($value) < $options['min_length']) {
            $this->errorMessage = "Value must be at least {$options['min_length']} characters long";
            return false;
        }

        // Проверяем максимальную длину
        if (isset($options['max_length']) && strlen($value) > $options['max_length']) {
            $this->errorMessage = "Value must be no more than {$options['max_length']} characters long";
            return false;
        }

        // Проверяем что не пустая строка
        if (isset($options['not_empty']) && $options['not_empty'] && trim($value) === '') {
            $this->errorMessage = "Value cannot be empty";
            return false;
        }

        // Проверяем список допустимых значений
        if (isset($options['allowed']) && !in_array($value, $options['allowed'], true)) {
            $allowedValues = implode(', ', $options['allowed']);
            $this->errorMessage = "Value must be one of: {$allowedValues}";
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
        return 'string';
    }
}
