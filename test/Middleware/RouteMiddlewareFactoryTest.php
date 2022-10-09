<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\RouteMiddlewareFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/** @covers \Mezzio\Router\Middleware\RouteMiddlewareFactory */
final class RouteMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private RouteMiddlewareFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new RouteMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfRouterServiceIsMissing(): void
    {
        $this->container
            ->expects(self::once())
            ->method('has')
            ->with(RouterInterface::class)
            ->willReturn(false);

        $this->expectException(MissingDependencyException::class);

        ($this->factory)($this->container);
    }

    public function testFactoryProducesRouteMiddlewareWhenAllDependenciesPresent(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $this->container
            ->expects(self::once())
            ->method('has')
            ->with(RouterInterface::class)
            ->willReturn(true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with(RouterInterface::class)
            ->willReturn($router);

        ($this->factory)($this->container);
    }

    public function testFactoryAllowsSpecifyingRouterServiceViaConstructor(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $this->container
            ->expects(self::once())
            ->method('has')
            ->with(Router::class)
            ->willReturn(true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with(Router::class)
            ->willReturn($router);

        $factory = new RouteMiddlewareFactory(Router::class);

        $middleware = $factory($this->container);

        self::assertSame($router, $middleware->getRouter());
    }

    public function testFactoryIsSerializable(): void
    {
        $factory = RouteMiddlewareFactory::__set_state([
            'routerServiceName' => Router::class,
        ]);

        self::assertSame(Router::class, $factory->getRouterServiceName());
    }
}
