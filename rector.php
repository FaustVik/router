<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/examples',
        __DIR__ . '/benchmarks',
    ])
    ->withParallel()
    ->withCache(__DIR__ . '/var/rector')
    ->withDeadCodeLevel(2)
    ->withCodeQualityLevel(2);
