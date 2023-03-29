<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * Create and return an ImplicitOptionsMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - \Psr\Http\Message\ResponseFactoryInterface, which should resolve to a factory
 *   that will produce an empty \Psr\Http\Message\ResponseInterface instance.
 *
 * @final
 */
class ImplicitOptionsMiddlewareFactory
{
    /**
     * @throws MissingDependencyException If the ResponseFactoryInterface service is missing.
     */
    public function __invoke(ContainerInterface $container): ImplicitOptionsMiddleware
    {
        if (! $container->has(ResponseFactoryInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                ResponseFactoryInterface::class,
                ImplicitOptionsMiddleware::class
            );
        }

        return new ImplicitOptionsMiddleware(
            $container->get(ResponseFactoryInterface::class),
        );
    }
}
