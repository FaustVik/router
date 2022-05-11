<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\interfaces\Router\Components\CheckHttpMethodInterface;
use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\interfaces\Router\Components\RunnerInterface;

final class Config implements ConfigInterface
{
    private ?RunnerInterface          $runner  = null;
    private ?CheckHttpMethodInterface $checker = null;

    public function setRunner(RunnerInterface $runner = null): void
    {
        $this->runner = $runner;
        $this->setDefaultRunner();
    }

    public function getRunner(): RunnerInterface
    {
        $this->setDefaultRunner();
        return $this->runner;
    }

    protected function setDefaultRunner(): void
    {
        if (!$this->runner) {
            $this->runner = new Runner();
        }
    }

    public function setCheckerHttpMethod(CheckHttpMethodInterface $checker = null): void
    {
        $this->checker = $checker;
        $this->setDefaultCheckerHttpMethod();
    }

    public function getCheckerHttpMethod(): CheckHttpMethodInterface
    {
        $this->setDefaultCheckerHttpMethod();
        return $this->checker;
    }

    protected function setDefaultCheckerHttpMethod(): void
    {
        if (!$this->checker) {
            $this->checker = new CheckerHttpMethod();
        }
    }
}
