<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Router;

use Psr\Container\ContainerInterface;

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
     * @throws Exception\MissingDependencyException if the RouterInterface service is
     *     missing.
     */
    public function __invoke(ContainerInterface $container) : RouteCollector
    {
        if (! $container->has(RouterInterface::class)
            && ! $container->has(\Zend\Expressive\Router\RouterInterface::class)
        ) {
            throw Exception\MissingDependencyException::dependencyForService(
                RouterInterface::class,
                RouteCollector::class
            );
        }

        return new RouteCollector(
            $container->has(RouterInterface::class)
                ? $container->get(RouterInterface::class)
                : $container->get(\Zend\Expressive\Router\RouterInterface::class)
        );
    }
}
