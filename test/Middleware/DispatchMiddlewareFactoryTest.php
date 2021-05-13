<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\DispatchMiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class DispatchMiddlewareFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testFactoryProducesDispatchMiddleware(): void
    {
        $container  = $this->prophesize(ContainerInterface::class)->reveal();
        $factory    = new DispatchMiddlewareFactory();
        $middleware = $factory($container);
        $this->assertInstanceOf(DispatchMiddleware::class, $middleware);
    }
}
