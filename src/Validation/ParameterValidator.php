<?php

declare(strict_types=1);

namespace FaustVik\Router\Validation;

use FaustVik\Router\exceptions\ValidationException;

class ParameterValidator
{
    /**
     * @param ParameterValidationRule[] $rules
     * @throws ValidationException
     */
    public function validate(array $parameters, array $rules): void
    {
        foreach ($rules as $rule) {
            $parameterName = $rule->getParameterName();
            $parameterValue = $parameters[$parameterName] ?? null;

            // Проверяем обязательные параметры.
            if ($rule->isRequired() && ($parameterValue === null || $parameterValue === '')) {
                throw new ValidationException($parameterName, $parameterValue ?? '', ['Parameter is required']);
            }

            // Если параметр не обязательный и не передан, пропускаем валидацию.
            if (!$rule->isRequired() && ($parameterValue === null || $parameterValue === '')) {
                continue;
            }

            // Выполняем валидацию.
            $errors = [];
            foreach ($rule->getValidators() as $validatorConfig) {
                $validator = $validatorConfig['validator'];
                $options = $validatorConfig['options'];

                if (!$validator->validate($parameterValue, $options)) {
                    $errors[] = $validator->getErrorMessage();
                }
            }

            // Если есть ошибки, выбрасываем исключение.
            if (!empty($errors)) {
                throw new ValidationException($parameterName, $parameterValue, $errors);
            }
        }
    }
}
