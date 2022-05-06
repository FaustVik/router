<?php

namespace FaustVik\Router\Additional;

use FaustVik\Router\interfaces\Additional\AdditionalActionInterface;

final class AdditionalAction implements AdditionalActionInterface
{
    protected string $action;
    protected string $class;

    public static function create(string $class, string $action): self
    {
        $self = new self();

        $self->class  = $class;
        $self->action = $action;

        return $self;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }
}
