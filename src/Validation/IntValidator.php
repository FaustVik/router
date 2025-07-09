<?php

declare(strict_types=1);

namespace FaustVik\Router\Validation;

use FaustVik\Router\interfaces\Validation\ParameterValidatorInterface;

class IntValidator implements ParameterValidatorInterface
{
    private string $errorMessage = '';

    public function validate(string $value, array $options = []): bool
    {
        if (!is_numeric($value) || !ctype_digit($value) && !($value[0] === '-' && ctype_digit(substr($value, 1)))) {
            $this->errorMessage = "Value must be an integer";
            return false;
        }

        $intValue = (int) $value;

        // Проверяем минимальное значение
        if (isset($options['min']) && $intValue < $options['min']) {
            $this->errorMessage = "Value must be greater than or equal to {$options['min']}";
            return false;
        }

        // Проверяем максимальное значение
        if (isset($options['max']) && $intValue > $options['max']) {
            $this->errorMessage = "Value must be less than or equal to {$options['max']}";
            return false;
        }

        return true;
    }//end validate()

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }//end getErrorMessage()

    public function getName(): string
    {
        return 'int';
    }//end getName()
}//end class
