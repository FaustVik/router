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
    }

    public static function for(string $parameterName): self
    {
        return new self($parameterName);
    }

    public function int(array $options = []): self
    {
        $this->validators[] = [
            'validator' => new IntValidator(),
            'options' => $options
        ];
        return $this;
    }

    public function string(array $options = []): self
    {
        $this->validators[] = [
            'validator' => new StringValidator(),
            'options' => $options
        ];
        return $this;
    }

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
    }

    public function email(): self
    {
        $this->validators[] = [
            'validator' => new EmailValidator(),
            'options' => []
        ];
        return $this;
    }

    public function uuid(): self
    {
        $this->validators[] = [
            'validator' => new UuidValidator(),
            'options' => []
        ];
        return $this;
    }

    public function slug(array $options = []): self
    {
        $this->validators[] = [
            'validator' => new SlugValidator(),
            'options' => $options
        ];
        return $this;
    }

    public function custom(ParameterValidatorInterface $validator, array $options = []): self
    {
        $this->validators[] = [
            'validator' => $validator,
            'options' => $options
        ];
        return $this;
    }

    public function optional(): self
    {
        $this->required = false;
        return $this;
    }

    public function required(): self
    {
        $this->required = true;
        return $this;
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function getValidators(): array
    {
        return $this->validators;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
} 