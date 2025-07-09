<?php

declare(strict_types=1);

namespace FaustVik\Router\Validation;

use FaustVik\Router\interfaces\Validation\ParameterValidatorInterface;

class ParameterValidationRule
{
    private string $parameterName;
    private array $validators = [];
    private bool $required = true;

    public function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }//end __construct()

    public static function for(string $parameterName): self
    {
        return new self($parameterName);
    }//end for()

    public function int(array $options = []): self
    {
        $this->validators[] = [
            'validator' => new IntValidator(),
            'options' => $options
        ];
        return $this;
    }//end int()

    public function string(array $options = []): self
    {
        $this->validators[] = [
            'validator' => new StringValidator(),
            'options' => $options
        ];
        return $this;
    }//end string()

    public function regex(string $pattern, string $message = null): self
    {
        $options = ['pattern' => $pattern];
        if ($message) {
            $options['message'] = $message;
        }

        $this->validators[] = [
            'validator' => new RegexValidator(),
            'options' => $options
        ];
        return $this;
    }//end regex()

    public function email(): self
    {
        $this->validators[] = [
            'validator' => new EmailValidator(),
            'options' => []
        ];
        return $this;
    }//end email()

    public function uuid(): self
    {
        $this->validators[] = [
            'validator' => new UuidValidator(),
            'options' => []
        ];
        return $this;
    }//end uuid()

    public function slug(array $options = []): self
    {
        $this->validators[] = [
            'validator' => new SlugValidator(),
            'options' => $options
        ];
        return $this;
    }//end slug()

    public function custom(ParameterValidatorInterface $validator, array $options = []): self
    {
        $this->validators[] = [
            'validator' => $validator,
            'options' => $options
        ];
        return $this;
    }//end custom()

    public function optional(): self
    {
        $this->required = false;
        return $this;
    }//end optional()

    public function required(): self
    {
        $this->required = true;
        return $this;
    }//end required()

    public function getParameterName(): string
    {
        return $this->parameterName;
    }//end getParameterName()

    public function getValidators(): array
    {
        return $this->validators;
    }//end getValidators()

    public function isRequired(): bool
    {
        return $this->required;
    }//end isRequired()
}//end class
