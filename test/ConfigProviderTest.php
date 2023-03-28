<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Mezzio\Router\ConfigProvider;
use Mezzio\Router\Middleware;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteCollectorFactory;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\TestCase;

/** @covers \Mezzio\Router\ConfigProvider */
final class ConfigProviderTest extends TestCase
{
    public function testProviderProvidesFactoriesForAllMiddleware(): void
    {
        $provider = new ConfigProvider();

        self::assertSame([
            'dependencies' => [
                'factories' => [
                    Middleware\DispatchMiddleware::class         => Middleware\DispatchMiddlewareFactory::class,
                    Middleware\ImplicitHeadMiddleware::class     => Middleware\ImplicitHeadMiddlewareFactory::class,
                    Middleware\ImplicitOptionsMiddleware::class  => Middleware\ImplicitOptionsMiddlewareFactory::class,
                    Middleware\MethodNotAllowedMiddleware::class => Middleware\MethodNotAllowedMiddlewareFactory::class,
                    Middleware\RouteMiddleware::class            => Middleware\RouteMiddlewareFactory::class,
                    RouteCollector::class                        => RouteCollectorFactory::class,
                ],
                'aliases'   => [
                    RouteCollectorInterface::class => RouteCollector::class,
                ],
            ],
        ], $provider());
    }
}
