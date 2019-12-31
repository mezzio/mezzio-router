<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\Middleware\RouteMiddlewareFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class RouteMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var RouteMiddlewareFactory */
    private $factory;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new RouteMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfRouterServiceIsMissing()
    {
        $this->container->has(RouterInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Router\RouterInterface::class)->willReturn(false);
        $this->container->has(ResponseInterface::class)->shouldNotBeCalled();

        $this->expectException(MissingDependencyException::class);
        $this->factory->__invoke($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfResponseServiceIsMissing()
    {
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->has(ResponseInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        $this->factory->__invoke($this->container->reveal());
    }

    public function testFactoryProducesRouteMiddlewareWhenAllDependenciesPresent()
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->get(RouterInterface::class)->willReturn($router);

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = function () use ($response) {
            return $response;
        };
        $this->container->has(ResponseInterface::class)->willReturn(true);
        $this->container->get(ResponseInterface::class)->willReturn($responseFactory);

        $middleware = $this->factory->__invoke($this->container->reveal());

        $this->assertInstanceOf(RouteMiddleware::class, $middleware);
    }

    public function testFactoryProducesRouteMiddlewareWhenResponseInstanceReturnedFromContainer()
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->get(RouterInterface::class)->willReturn($router);

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->container->has(ResponseInterface::class)->willReturn(true);
        $this->container->get(ResponseInterface::class)->willReturn($response);

        $middleware = $this->factory->__invoke($this->container->reveal());

        $this->assertInstanceOf(RouteMiddleware::class, $middleware);
    }
}
