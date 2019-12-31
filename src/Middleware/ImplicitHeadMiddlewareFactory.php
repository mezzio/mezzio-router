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

use const Mezzio\Router\IMPLICIT_HEAD_MIDDLEWARE_RESPONSE;
use const Mezzio\Router\IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY;

/**
 * Create and return an ImplicitHeadMiddleware instance.
 *
 * This factory depends on two other services:
 *
 * - IMPLICIT_HEAD_MIDDLEWARE_RESPONSE, which should resolve to a
 *   Psr\Http\Message\ResponseInterface instance.
 * - IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY, which should resolve to a
 *   callable that will produce an empty Psr\Http\Message\StreamInterface
 *   instance.
 */
class ImplicitHeadMiddlewareFactory
{
    /**
     * @throws MissingDependencyException if either the IMPLICIT_HEAD_MIDDLEWARE_RESPONSE
     *     or IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY services are missing.
     */
    public function __invoke(ContainerInterface $container) : ImplicitHeadMiddleware
    {
        if (! $container->has(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)
            && ! $container->has(\const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_RESPONSE::class)
        ) {
            throw MissingDependencyException::dependencyForService(
                IMPLICIT_HEAD_MIDDLEWARE_RESPONSE,
                ImplicitHeadMiddleware::class
            );
        }

        if (! $container->has(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)
            && ! $container->has(\const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY::class)
        ) {
            throw MissingDependencyException::dependencyForService(
                IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY,
                ImplicitHeadMiddleware::class
            );
        }

        return new ImplicitHeadMiddleware(
            $container->has(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE) ? $container->get(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE) : $container->get(\const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_RESPONSE::class),
            $container->has(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY) ? $container->get(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY) : $container->get(\const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY::class)
        );
    }
}
