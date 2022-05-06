<?php

namespace FaustVik\Router\test;

class TitClass
{
    private string $gf;

    public function __construct(string $gf)
    {
        $this->gf = $gf;
    }

    public function getGf(): string
    {
        return $this->gf;
    }
}
