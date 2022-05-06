<?php

declare(strict_types=1);

namespace FaustVik\Router\Router;

use FaustVik\Router\interfaces\Router\ConfigInterface;
use FaustVik\Router\interfaces\RunnerInterface;

final class Config implements ConfigInterface
{
    private ?RunnerInterface $runner = null;

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
}
