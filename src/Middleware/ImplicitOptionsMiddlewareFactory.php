<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Psr\Container\ContainerInterface;

use const Mezzio\Router\IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE;

/**
 * Create and return an ImplicitOptionsMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE, which should resolve to a
 *     Psr\Http\Message\ResponseInterface instance.
 */
class ImplicitOptionsMiddlewareFactory
{
    /**
     * @throws MissingDependencyException if the IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE
     *     service is missing.
     */
    public function __invoke(ContainerInterface $container) : ImplicitOptionsMiddleware
    {
        if (! $container->has(IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE)
            && ! $container->has(\const Zend\Expressive\Router\IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE::class)
        ) {
            throw MissingDependencyException::dependencyForService(
                IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE,
                ImplicitOptionsMiddleware::class
            );
        }

        return new ImplicitOptionsMiddleware(
            $container->has(IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE) ? $container->get(IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE) : $container->get(\const Zend\Expressive\Router\IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE::class)
        );
    }
}
