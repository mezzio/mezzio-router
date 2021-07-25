<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Expressive\Router\RouterInterface as ZendExpressiveRouterInterface;

/**
 * Create and return an ImplicitHeadMiddleware instance.
 *
 * This factory depends on two other services:
 *
 * - Mezzio\Router\RouterInterface, which should resolve to an
 *   instance of that interface.
 * - Psr\Http\Message\StreamInterface, which should resolve to a callable
 *   that will produce an empty Psr\Http\Message\StreamInterface instance.
 */
class ImplicitHeadMiddlewareFactory
{
    /**
     * @throws MissingDependencyException If either the Mezzio\Router\RouterInterface
     *     or Psr\Http\Message\StreamInterface services are missing.
     */
    public function __invoke(ContainerInterface $container): ImplicitHeadMiddleware
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
            throw MissingDependencyException::dependencyForService(
                RouterInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        if (! $container->has(StreamInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                StreamInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        return new ImplicitHeadMiddleware(
            $hasRouter
                ? $container->get(RouterInterface::class)
                : $container->get(ZendExpressiveRouterInterface::class),
            $container->get(StreamInterface::class)
        );
    }
}
