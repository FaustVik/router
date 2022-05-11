<?php

namespace FaustVik\Router\interfaces\Router;

use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\Route\RoutesCollection;

interface RouterInterface
{
    public function run(): void;

    public function setCollection(RoutesCollection $collection): self;

    public function setUri(string $uri): self;

    public function getUri(): string;

    public function setConfig(ConfigInterface $config);

    public function getConfig(): ConfigInterface;
}
