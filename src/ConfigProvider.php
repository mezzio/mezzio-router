<?php

declare(strict_types=1);

namespace Mezzio\Router;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        // @codingStandardsIgnoreStart
        return [
            // Legacy Zend Framework aliases
            'aliases' => [
                'Zend\Expressive\Router\Middleware\DispatchMiddleware' => Middleware\DispatchMiddleware::class,
                'Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware' => Middleware\ImplicitHeadMiddleware::class,
                'Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware' => Middleware\ImplicitOptionsMiddleware::class,
                'Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware' => Middleware\MethodNotAllowedMiddleware::class,
                'Zend\Expressive\Router\Middleware\RouteMiddleware' => Middleware\RouteMiddleware::class,
                'Zend\Expressive\Router\RouteCollector' => RouteCollector::class,
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
