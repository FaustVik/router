<?php

namespace FaustVik\Router\test;

class BaseController
{
    protected int      $i = 0;
    protected string   $g;
    protected TitClass $tit_class;

    public function __construct(TitClass $class)
    {
        $this->i         = random_int(0, 15);
        $this->tit_class = $class;
    }
}
