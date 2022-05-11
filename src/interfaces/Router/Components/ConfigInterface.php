<?php

namespace FaustVik\Router\interfaces\Router\Components;

interface ConfigInterface
{
    public function setRunner(RunnerInterface $runner = null): void;

    public function getRunner(): RunnerInterface;

    public function setCheckerHttpMethod(CheckHttpMethodInterface $checker = null): void;

    public function getCheckerHttpMethod(): CheckHttpMethodInterface;
}
