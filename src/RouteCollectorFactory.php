<?php

declare(strict_types=1);

namespace Mezzio\Router;

use ArrayAccess;
use ArrayObject;
use LogicException;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface as ZendExpressiveRouterInterface;

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
     * @throws Exception\MissingDependencyException If the RouterInterface service is
     *     missing.
     */
    public function __invoke(ContainerInterface $container): RouteCollector
    {
        $hasRouter           = $container->has(RouterInterface::class);
        $hasDeprecatedRouter = false;

        if (! $hasRouter) {
            $hasDeprecatedRouter = $container->has(ZendExpressiveRouterInterface::class);
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
                : $container->get(ZendExpressiveRouterInterface::class),
            $this->getDetectDuplicatesFlag($container)
        );
    }

    private function getDetectDuplicatesFlag(ContainerInterface $container): bool
    {
        if (! $container->has('config')) {
            return true;
        }

        $config = $container->get('config');

        $config = is_array($config) ? new ArrayObject($config) : $config;

        if (! $config instanceof ArrayAccess) {
            throw new LogicException(sprintf('Config must be an array or implement %s.', ArrayAccess::class));
        }

        if (! $config->offsetExists(RouteCollector::class)) {
            return true;
        }

        /** @var array $config */
        $config = $config->offsetGet(RouteCollector::class);

        if (! isset($config['detect_duplicates'])) {
            return true;
        }

        return (bool) $config['detect_duplicates'];
    }
}
