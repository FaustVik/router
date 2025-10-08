<?php

declare(strict_types=1);

namespace FaustVik\Router\DI;

use DI\Container as PHPDIContainer;
use FaustVik\Router\DI\Adapters\PhpDiContainerAdapter;
use FaustVik\Router\DI\Adapters\PimpleContainerAdapter;
use FaustVik\Router\DI\Adapters\SymfonyContainerAdapter;
use FaustVik\Router\interfaces\DI\RouterContainerInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for creating container adapters
 * Automatically detects container type and creates appropriate adapter
 */
class ContainerAdapterFactory
{
    /**
     * Create adapter for given container
     *
     * @throws \InvalidArgumentException
     */
    public static function createFor(object $container): RouterContainerInterface
    {
        // If it's already a RouterContainerInterface, return as-is
        if ($container instanceof RouterContainerInterface) {
            return $container;
        }

        // Detect PHP-DI container
        if ($container instanceof PHPDIContainer) {
            return new PhpDiContainerAdapter($container);
        }

        // Detect Symfony container (check for common Symfony container classes)
        if (self::isSymfonyContainer($container)) {
            if (!$container instanceof ContainerInterface) {
                throw new \InvalidArgumentException('Symfony container must implement PSR-11 ContainerInterface');
            }
            return new SymfonyContainerAdapter($container);
        }

        // Detect Pimple container
        if (self::isPimpleContainer($container)) {
            if (!$container instanceof ContainerInterface) {
                throw new \InvalidArgumentException('Pimple container must implement PSR-11 ContainerInterface');
            }
            return new PimpleContainerAdapter($container);
        }

        // Generic PSR-11 container - try to wrap with Symfony adapter
        if ($container instanceof ContainerInterface) {
            return new SymfonyContainerAdapter($container);
        }

        throw new \InvalidArgumentException(
            'Unsupported container type: ' . get_class($container) .
            '. Container must implement PSR-11 ContainerInterface or be a supported container type.'
        );
    }

    /**
     * Check if container is a Symfony container
     */
    private static function isSymfonyContainer(object $container): bool
    {
        $className = get_class($container);

        // Check for common Symfony container classes
        $symfonyContainerClasses = [
            'Symfony\Component\DependencyInjection\Container',
            'Symfony\Component\DependencyInjection\ContainerBuilder',
            'Symfony\Component\DependencyInjection\TaggedContainerInterface',
            'Symfony\Component\DependencyInjection\ResettableContainerInterface',
        ];

        foreach ($symfonyContainerClasses as $symfonyClass) {
            if (is_a($container, $symfonyClass)) {
                return true;
            }
        }

        // Check if it's a Symfony container by namespace
        return str_contains($className, 'Symfony\\Component\\DependencyInjection\\');
    }

    /**
     * Check if container is a Pimple container
     */
    private static function isPimpleContainer(object $container): bool
    {
        $className = get_class($container);

        // Check for Pimple container classes
        $pimpleContainerClasses = [
            'Pimple\Container',
            'Pimple\Psr11\Container',
        ];

        foreach ($pimpleContainerClasses as $pimpleClass) {
            if (is_a($container, $pimpleClass)) {
                return true;
            }
        }

        // Check if it's a Pimple container by namespace
        return str_contains($className, 'Pimple\\');
    }

    /**
     * Create adapter by container type name
     *
     * @throws \InvalidArgumentException
     */
    public static function createByType(string $type, object $container): RouterContainerInterface
    {
        $lowerType = strtolower($type);

        if ($lowerType === 'php-di' || $lowerType === 'phpdi') {
            if (!$container instanceof PHPDIContainer) {
                throw new \InvalidArgumentException('Container must be an instance of DI\Container for php-di type');
            }
            return new PhpDiContainerAdapter($container);
        }

        if ($lowerType === 'symfony') {
            if (!$container instanceof ContainerInterface) {
                throw new \InvalidArgumentException('Container must implement PSR-11 ContainerInterface for symfony type');
            }
            return new SymfonyContainerAdapter($container);
        }

        if ($lowerType === 'pimple') {
            if (!$container instanceof ContainerInterface) {
                throw new \InvalidArgumentException('Container must implement PSR-11 ContainerInterface for pimple type');
            }
            return new PimpleContainerAdapter($container);
        }

        throw new \InvalidArgumentException("Unsupported container type: {$type}");
    }

    /**
     * Get supported container types
     *
     * @return array<string, class-string>
     */
    public static function getSupportedTypes(): array
    {
        return [
            'php-di' => PhpDiContainerAdapter::class,
            'symfony' => SymfonyContainerAdapter::class,
            'pimple' => PimpleContainerAdapter::class,
        ];
    }

    /**
     * Check if container type is supported
     */
    public static function isSupported(object $container): bool
    {
        try {
            self::createFor($container);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
