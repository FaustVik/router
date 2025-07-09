<?php

declare(strict_types=1);

namespace FaustVik\Router\Validation;

use FaustVik\Router\interfaces\Validation\ParameterValidatorInterface;

class SlugValidator implements ParameterValidatorInterface
{
    private string $errorMessage = '';

    public function validate(string $value, array $options = []): bool
    {
        // Slug pattern: lowercase letters, numbers, hyphens, underscores
        $pattern = '/^[a-z0-9\-_]+$/';

        if (!preg_match($pattern, $value)) {
            $this->errorMessage = "Value must be a valid slug (lowercase letters, numbers, hyphens, underscores only)";
            return false;
        }

        // Проверяем минимальную длину
        if (isset($options['min_length']) && strlen($value) < $options['min_length']) {
            $this->errorMessage = "Slug must be at least {$options['min_length']} characters long";
            return false;
        }

        // Проверяем максимальную длину
        if (isset($options['max_length']) && strlen($value) > $options['max_length']) {
            $this->errorMessage = "Slug must be no more than {$options['max_length']} characters long";
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
        return 'slug';
    }
}
