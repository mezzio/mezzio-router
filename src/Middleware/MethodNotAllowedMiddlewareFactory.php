<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * Create and return a MethodNotAllowedMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - \Psr\Http\Message\ResponseFactoryInterface, which should resolve to a factory
 *   that will produce an empty Psr\Http\Message\ResponseInterface instance.
 *
 * @final
 */
class MethodNotAllowedMiddlewareFactory
{
    /**
     * @throws MissingDependencyException If the ResponseFactoryInterface service is missing.
     */
    public function __invoke(ContainerInterface $container): MethodNotAllowedMiddleware
    {
        if (! $container->has(ResponseFactoryInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                ResponseFactoryInterface::class,
                MethodNotAllowedMiddleware::class
            );
        }

        return new MethodNotAllowedMiddleware(
            $container->get(ResponseFactoryInterface::class),
        );
    }
}
