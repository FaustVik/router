<?php

declare(strict_types=1);

namespace FaustVik\Router\interfaces\Validation;

interface ParameterValidatorInterface
{
    public function validate(string $value, array $options = []): bool;

    public function getErrorMessage(): string;

    public function getName(): string;
}
