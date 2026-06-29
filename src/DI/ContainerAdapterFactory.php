<?php

declare(strict_types=1);

namespace FaustVik\Router\DI;

use DI\Container;
use FaustVik\Router\Interfaces\DI\RouterContainerInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Factory for creating container adapters
 * Automatically detects container type and creates appropriate adapter
 */
class ContainerAdapterFactory
{
    /**
     * @throws InvalidArgumentException
     */
    public static function createFor(object $container): RouterContainerInterface
    {
        if ($container instanceof RouterContainerInterface) {
            return $container;
        }

        $className = get_class($container);

        // PHP-DI
        if (str_contains($className, 'DI\\') && $container instanceof Container) {
            return new Adapters\PhpDiContainerAdapter($container);
        }

        // Symfony
        if (str_contains($className, 'Symfony\\Component\\DependencyInjection\\') && $container instanceof ContainerInterface) {
            return new Adapters\SymfonyContainerAdapter($container);
        }

        // Pimple
        if (str_contains($className, 'Pimple\\') && $container instanceof ContainerInterface) {
            return new Adapters\PimpleContainerAdapter($container);
        }

        // Generic PSR-11
        if ($container instanceof ContainerInterface) {
            return new Adapters\SymfonyContainerAdapter($container);
        }

        throw new InvalidArgumentException(
            'Unsupported container type: ' . $className
            . '. Container must implement PSR-11 ContainerInterface or be a supported container type.'
        );
    }

    /**
     * @return array<string, class-string>
     */
    public static function getSupportedTypes(): array
    {
        return [
            'php-di' => Adapters\PhpDiContainerAdapter::class,
            'symfony' => Adapters\SymfonyContainerAdapter::class,
            'pimple' => Adapters\PimpleContainerAdapter::class,
        ];
    }

    public static function isSupported(object $container): bool
    {
        try {
            self::createFor($container);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
