<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Mezzio\Router\ConfigProvider;
use Mezzio\Router\Middleware;
use Mezzio\Router\RouteCollector;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testProviderProvidesFactoriesForAllMiddleware(): void
    {
        $provider = new ConfigProvider();
        $config   = $provider();

        $this->assertTrue(isset($config['dependencies']['factories']));
        $factories = $config['dependencies']['factories'];
        $this->assertArrayHasKey(Middleware\DispatchMiddleware::class, $factories);
        $this->assertArrayHasKey(Middleware\ImplicitHeadMiddleware::class, $factories);
        $this->assertArrayHasKey(Middleware\ImplicitOptionsMiddleware::class, $factories);
        $this->assertArrayHasKey(Middleware\MethodNotAllowedMiddleware::class, $factories);
        $this->assertArrayHasKey(Middleware\RouteMiddleware::class, $factories);
        $this->assertArrayHasKey(RouteCollector::class, $factories);
    }
}
