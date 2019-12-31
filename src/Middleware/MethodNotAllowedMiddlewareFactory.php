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

use const Mezzio\Router\METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE;

/**
 * Create and return a MethodNotAllowedMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE, which should resolve to a
 *   Psr\Http\Message\ResponseInterface instance.
 */
class MethodNotAllowedMiddlewareFactory
{
    /**
     * @throws MissingDependencyException if the METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE
     *     service is missing.
     */
    public function __invoke(ContainerInterface $container) : MethodNotAllowedMiddleware
    {
        if (! $container->has(METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE)
            && ! $container->has(\const Zend\Expressive\Router\METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE::class)
        ) {
            throw MissingDependencyException::dependencyForService(
                METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE,
                MethodNotAllowedMiddleware::class
            );
        }

        return new MethodNotAllowedMiddleware(
            $container->has(METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE) ? $container->get(METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE) : $container->get(\const Zend\Expressive\Router\METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE::class)
        );
    }
}
