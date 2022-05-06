<?php

namespace FaustVik\Router\interfaces\Router;

use FaustVik\Router\interfaces\RunnerInterface;

interface ConfigInterface
{
    public function setRunner(RunnerInterface $runner = null): void;

    public function getRunner(): RunnerInterface;
}
