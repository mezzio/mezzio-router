<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Router;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        // @codingStandardsIgnoreStart
        return [
            // Legacy Zend Framework aliases
            'aliases' => [
                \Zend\Expressive\Router\Middleware\DispatchMiddleware::class => Middleware\DispatchMiddleware::class,
                \Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware::class => Middleware\ImplicitHeadMiddleware::class,
                \Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware::class => Middleware\ImplicitOptionsMiddleware::class,
                \Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware::class => Middleware\MethodNotAllowedMiddleware::class,
                \Zend\Expressive\Router\Middleware\RouteMiddleware::class => Middleware\RouteMiddleware::class,
                \Zend\Expressive\Router\RouteCollector::class => RouteCollector::class,
            ],
            'factories' => [
                Middleware\DispatchMiddleware::class         => Middleware\DispatchMiddlewareFactory::class,
                Middleware\ImplicitHeadMiddleware::class     => Middleware\ImplicitHeadMiddlewareFactory::class,
                Middleware\ImplicitOptionsMiddleware::class  => Middleware\ImplicitOptionsMiddlewareFactory::class,
                Middleware\MethodNotAllowedMiddleware::class => Middleware\MethodNotAllowedMiddlewareFactory::class,
                Middleware\RouteMiddleware::class            => Middleware\RouteMiddlewareFactory::class,
                RouteCollector::class                        => RouteCollectorFactory::class,
            ]
        ];
        // @codingStandardsIgnoreEnd
    }
}
