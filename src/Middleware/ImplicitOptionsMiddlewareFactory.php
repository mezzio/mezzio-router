<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Psr\Container\ContainerInterface;

/**
 * Create and return an ImplicitOptionsMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - Psr\Http\Message\ResponseInterface, which should resolve to a callable
 *   that will produce an empty Psr\Http\Message\ResponseInterface instance.
 */
class ImplicitOptionsMiddlewareFactory
{
    use Psr17ResponseFactoryTrait;

    /**
     * @throws MissingDependencyException If the Psr\Http\Message\ResponseInterface
     *     service is missing.
     */
    public function __invoke(ContainerInterface $container): ImplicitOptionsMiddleware
    {
        return new ImplicitOptionsMiddleware(
            $this->detectResponseFactory($container)
        );
    }
}
