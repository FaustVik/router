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
    }//end validate()

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }//end getErrorMessage()

    public function getName(): string
    {
        return 'email';
    }//end getName()
}//end class
