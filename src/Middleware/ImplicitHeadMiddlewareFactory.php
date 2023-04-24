<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\RouterInterface;
use Mezzio\Router\Stream\CallableStreamFactoryDecorator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Create and return an ImplicitHeadMiddleware instance.
 *
 * This factory depends on two other services:
 *
 * - Mezzio\Router\RouterInterface, which should resolve to an instance of that interface.
 * - Either Psr\Http\Message\StreamFactoryInterface or Psr\Http\Message\StreamInterface, which should resolve to a
 *   callable that will produce an empty Psr\Http\Message\StreamInterface instance.
 *
 * @final
 */
class ImplicitHeadMiddlewareFactory
{
    /**
     * @throws MissingDependencyException If either the Mezzio\Router\RouterInterface
     *     or Psr\Http\Message\StreamInterface services are missing.
     */
    public function __invoke(ContainerInterface $container): ImplicitHeadMiddleware
    {
        if (! $container->has(RouterInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                RouterInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        return new ImplicitHeadMiddleware(
            $container->get(RouterInterface::class),
            $this->detectStreamFactory($container),
        );
    }

    /**
     * BC Preserving StreamFactoryInterface Retrieval
     *
     * Preserves existing behaviour in the 3.x series by fetching a `StreamInterface` callable and wrapping it in a
     * decorator that implements StreamFactoryInterface. If `StreamInterface` callable is unavailable, attempt to
     * fetch a `StreamFactoryInterface`, throwing a MissingDependencyException if neither are found.
     *
     * @deprecated Will be removed in version 4.0.0
     */
    private function detectStreamFactory(ContainerInterface $container): StreamFactoryInterface
    {
        $hasStreamFactory      = $container->has(StreamFactoryInterface::class);
        $hasDeprecatedCallable = $container->has(StreamInterface::class);

        if (! $hasStreamFactory && ! $hasDeprecatedCallable) {
            throw MissingDependencyException::dependencyForService(
                StreamInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        if ($hasDeprecatedCallable) {
            return new CallableStreamFactoryDecorator($container->get(StreamInterface::class));
        }

        return $container->get(StreamFactoryInterface::class);
    }
}
