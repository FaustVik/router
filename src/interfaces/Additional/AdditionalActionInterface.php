<?php

namespace FaustVik\Router\interfaces\Additional;

interface AdditionalActionInterface
{
    public static function create(string $class, string $action): self;

    public function getClass(): string;

    public function getAction(): string;
}
