<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\DispatchMiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DispatchMiddlewareFactoryTest extends TestCase
{
    public function testFactoryProducesDispatchMiddleware(): void
    {
        $container  = $this->prophesize(ContainerInterface::class)->reveal();
        $factory    = new DispatchMiddlewareFactory();
        $middleware = $factory($container);
        $this->assertInstanceOf(DispatchMiddleware::class, $middleware);
    }
}
