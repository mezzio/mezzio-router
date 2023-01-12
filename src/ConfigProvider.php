<?php

declare(strict_types=1);

namespace Mezzio\Router;

/**
 * @psalm-type DependencyConfig = array{factories: array<class-string, class-string>}
 */
class ConfigProvider
{
    /** @return array{dependencies: DependencyConfig} */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /** @return DependencyConfig */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                Middleware\DispatchMiddleware::class         => Middleware\DispatchMiddlewareFactory::class,
                Middleware\ImplicitHeadMiddleware::class     => Middleware\ImplicitHeadMiddlewareFactory::class,
                Middleware\ImplicitOptionsMiddleware::class  => Middleware\ImplicitOptionsMiddlewareFactory::class,
                Middleware\MethodNotAllowedMiddleware::class => Middleware\MethodNotAllowedMiddlewareFactory::class,
                Middleware\RouteMiddleware::class            => Middleware\RouteMiddlewareFactory::class,
                RouteCollector::class                        => RouteCollectorFactory::class,
            ],
        ];
    }
}
