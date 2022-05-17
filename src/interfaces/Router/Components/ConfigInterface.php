<?php

namespace FaustVik\Router\interfaces\Router\Components;

interface ConfigInterface
{
    /**
     * Set custom Runner
     *
     * @param RunnerInterface $runner
     *
     * @return void
     */
    public function setRunner(RunnerInterface $runner): void;

    /**
     * Get custom Runner or get default Runner
     *
     * @return RunnerInterface
     */
    public function getRunner(): RunnerInterface;

    /**
     * Set checker for allow http methods
     *
     * @param CheckHttpMethodInterface $checker
     *
     * @return void
     */
    public function setCheckerHttpMethod(CheckHttpMethodInterface $checker): void;

    /**
     * Get checker for allow http methods
     *
     * @return CheckHttpMethodInterface
     */
    public function getCheckerHttpMethod(): CheckHttpMethodInterface;

    /**
     * Set custom matcher uri with route
     *
     * @param MatchingRouteInterface $matcher
     *
     * @return void
     */
    public function setMatcher(MatchingRouteInterface $matcher): void;

    /**
     * Get matcher
     *
     * @return MatchingRouteInterface
     */
    public function getMatch(): MatchingRouteInterface;
}
