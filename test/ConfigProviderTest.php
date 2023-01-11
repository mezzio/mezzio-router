<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Mezzio\Router\ConfigProvider;
use Mezzio\Router\Middleware;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteCollectorFactory;
use PHPUnit\Framework\TestCase;

/** @covers \Mezzio\Router\ConfigProvider */
final class ConfigProviderTest extends TestCase
{
    public function testProviderProvidesFactoriesForAllMiddleware(): void
    {
        $provider = new ConfigProvider();

        self::assertSame([
            'dependencies' => [
                'aliases'   => [
                    // @codingStandardsIgnoreStart
                    'Zend\Expressive\Router\Middleware\DispatchMiddleware' => Middleware\DispatchMiddleware::class,
                    'Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware' => Middleware\ImplicitHeadMiddleware::class,
                    'Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware' => Middleware\ImplicitOptionsMiddleware::class,
                    'Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware' => Middleware\MethodNotAllowedMiddleware::class,
                    'Zend\Expressive\Router\Middleware\RouteMiddleware' => Middleware\RouteMiddleware::class,
                    'Zend\Expressive\Router\RouteCollector' => RouteCollector::class,
                    // @codingStandardsIgnoreEnd
                ],
                'factories' => [
                    Middleware\DispatchMiddleware::class         => Middleware\DispatchMiddlewareFactory::class,
                    Middleware\ImplicitHeadMiddleware::class     => Middleware\ImplicitHeadMiddlewareFactory::class,
                    Middleware\ImplicitOptionsMiddleware::class  => Middleware\ImplicitOptionsMiddlewareFactory::class,
                    Middleware\MethodNotAllowedMiddleware::class => Middleware\MethodNotAllowedMiddlewareFactory::class,
                    Middleware\RouteMiddleware::class            => Middleware\RouteMiddlewareFactory::class,
                    RouteCollector::class                        => RouteCollectorFactory::class,
                ],
            ],
        ], $provider());
    }
}
