<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\Middleware\RouteMiddlewareFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

class RouteMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var RouteMiddlewareFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new RouteMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfRouterServiceIsMissing()
    {
        $this->container->has(RouterInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Router\RouterInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesRouteMiddlewareWhenAllDependenciesPresent()
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->get(RouterInterface::class)->willReturn($router);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(RouteMiddleware::class, $middleware);
    }

    public function testFactoryAllowsSpecifyingRouterServiceViaConstructor()
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(Router::class)->willReturn(true);
        $this->container->get(Router::class)->willReturn($router);

        $factory = new RouteMiddlewareFactory(Router::class);

        $middleware = $factory($this->container->reveal());

        $this->assertInstanceOf(RouteMiddleware::class, $middleware);
        $this->assertAttributeSame($router, 'router', $middleware);
    }

    public function testFactoryIsSerializable()
    {
        $factory = RouteMiddlewareFactory::__set_state([
            'routerServiceName' => Router::class,
        ]);

        $this->assertAttributeSame(Router::class, 'routerServiceName', $factory);
    }
}
