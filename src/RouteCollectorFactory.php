<?php

declare(strict_types=1);

namespace Mezzio\Router;

use ArrayAccess;
use LogicException;
use Psr\Container\ContainerInterface;

use function is_array;
use function sprintf;

/**
 * Create and return a RouteCollector instance.
 *
 * This factory depends on one other service:
 *
 * - Mezzio\Router\RouterInterface, which should resolve to
 *   a class implementing that interface.
 */
class RouteCollectorFactory
{
    /**
     * @throws Exception\MissingDependencyException If the RouterInterface service is missing.
     */
    public function __invoke(ContainerInterface $container): RouteCollector
    {
        $hasRouter           = $container->has(RouterInterface::class);
        $hasDeprecatedRouter = false;

        if (! $hasRouter) {
            $hasDeprecatedRouter = $container->has('Zend\Expressive\Router\RouterInterface');
        }

        if (
            ! $hasRouter
            && ! $hasDeprecatedRouter
        ) {
            throw Exception\MissingDependencyException::dependencyForService(
                RouterInterface::class,
                RouteCollector::class
            );
        }

        return new RouteCollector(
            $hasRouter
                ? $container->get(RouterInterface::class)
                : $container->get('Zend\Expressive\Router\RouterInterface'),
            $this->getDetectDuplicatesFlag($container)
        );
    }

    private function getDetectDuplicatesFlag(ContainerInterface $container): bool
    {
        if (! $container->has('config')) {
            return true;
        }

        $config = $container->get('config');
        if (! is_array($config) && ! $config instanceof ArrayAccess) {
            throw new LogicException(sprintf('Config must be an array or implement %s.', ArrayAccess::class));
        }

        if (! isset($config[RouteCollector::class])) {
            return true;
        }

        $collectorOptions = $config[RouteCollector::class] ?? [];

        if (!is_array($collectorOptions)  || ! isset($config['detect_duplicates'])) {
            return true;
        }

        return (bool) $config['detect_duplicates'];
    }
}
