<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Create and return an ImplicitHeadMiddleware instance.
 *
 * This factory depends on two other services:
 *
 * - Mezzio\Router\RouterInterface, which should resolve to an instance of that interface.
 * - Psr\Http\Message\StreamFactoryInterface which should resolve to an instance of that interface.
 */
final class ImplicitHeadMiddlewareFactory
{
    /**
     * @throws MissingDependencyException If either the Mezzio\Router\RouterInterface
     *     or Psr\Http\Message\StreamFactoryInterface services are missing.
     */
    public function __invoke(ContainerInterface $container): ImplicitHeadMiddleware
    {
        if (! $container->has(RouterInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                RouterInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        if (! $container->has(StreamFactoryInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                StreamFactoryInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        return new ImplicitHeadMiddleware(
            $container->get(RouterInterface::class),
            $container->get(StreamFactoryInterface::class),
        );
    }
}
