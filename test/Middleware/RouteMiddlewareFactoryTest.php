<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Closure;
use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\Middleware\RouteMiddlewareFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface as ZendExpressiveRouterInterface;

class RouteMiddlewareFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var RouteMiddlewareFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory   = new RouteMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfRouterServiceIsMissing(): void
    {
        $this->container->has(RouterInterface::class)->willReturn(false);
        $this->container->has(ZendExpressiveRouterInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesRouteMiddlewareWhenAllDependenciesPresent(): void
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->get(RouterInterface::class)->willReturn($router);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(RouteMiddleware::class, $middleware);
    }

    public function testFactoryAllowsSpecifyingRouterServiceViaConstructor(): void
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(Router::class)->willReturn(true);
        $this->container->get(Router::class)->willReturn($router);

        $factory = new RouteMiddlewareFactory(Router::class);

        $middleware = $factory($this->container->reveal());

        $this->assertInstanceOf(RouteMiddleware::class, $middleware);

        $middlewareRouter = Closure::bind(function () {
            return $this->router;
        }, $middleware, RouteMiddleware::class)();
        $this->assertSame($router, $middlewareRouter);
    }

    public function testFactoryIsSerializable(): void
    {
        $factory = RouteMiddlewareFactory::__set_state([
            'routerServiceName' => Router::class,
        ]);

        $factoryRouterServiceName = Closure::bind(function () {
            return $this->routerServiceName;
        }, $factory, RouteMiddlewareFactory::class)();

        $this->assertSame(Router::class, $factoryRouterServiceName);
    }
}
