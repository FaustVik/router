<?php

namespace FaustVik\Router\interfaces\Router;

interface RoutingInterface
{
    public function run(): void;

    public function setCollection(\FaustVik\Router\Route\RoutesCollection $collection): self;

    public function setUri(string $uri): self;

    public function getUri(): string;

    public function setConfig(ConfigInterface $config);

    public function getConfig(): ConfigInterface;
}
