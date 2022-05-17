<?php

declare(strict_types=1);

namespace FaustVik\Router\Router\Components;

use FaustVik\Router\interfaces\Router\Components\CheckHttpMethodInterface;
use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\interfaces\Router\Components\MatchingRouteInterface;
use FaustVik\Router\interfaces\Router\Components\RunnerInterface;

final class Config implements ConfigInterface
{
    private RunnerInterface          $runner;
    private CheckHttpMethodInterface $checker;
    private MatchingRouteInterface   $match;

    public function __construct()
    {
        $this->runner  = new Runner();
        $this->checker = new CheckerHttpMethod();
        $this->match   = new Matching();
    }

    public function setRunner(RunnerInterface $runner): void
    {
        $this->runner = $runner;
    }

    public function getRunner(): RunnerInterface
    {
        return $this->runner;
    }

    public function setCheckerHttpMethod(CheckHttpMethodInterface $checker): void
    {
        $this->checker = $checker;
    }

    public function getCheckerHttpMethod(): CheckHttpMethodInterface
    {
        return $this->checker;
    }

    /**
     * @return MatchingRouteInterface
     */
    public function getMatch(): MatchingRouteInterface
    {
        return $this->match;
    }

    /**
     * @param MatchingRouteInterface $matcher
     */
    public function setMatcher(MatchingRouteInterface $matcher): void
    {
        $this->match = $matcher;
    }
}
