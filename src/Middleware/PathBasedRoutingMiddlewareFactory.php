<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

/**
 * Create and return a PathBasedRoutingMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - Mezzio\Router\RouterInterface, which should resolve to
 *   a class implementing that interface.
 */
class PathBasedRoutingMiddlewareFactory
{
    /**
     * @throws MissingDependencyException if the RouterInterface service is
     *     missing.
     */
    public function __invoke(ContainerInterface $container) : PathBasedRoutingMiddleware
    {
        if (! $container->has(RouterInterface::class)
            && ! $container->has(\Zend\Expressive\Router\RouterInterface::class)
        ) {
            throw MissingDependencyException::dependencyForService(
                RouterInterface::class,
                PathBasedRoutingMiddleware::class
            );
        }

        return new PathBasedRoutingMiddleware($container->has(RouterInterface::class) ? $container->get(RouterInterface::class) : $container->get(\Zend\Expressive\Router\RouterInterface::class));
    }
}
