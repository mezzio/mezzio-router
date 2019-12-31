<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\PathBasedRoutingMiddleware;
use Mezzio\Router\Middleware\PathBasedRoutingMiddlewareFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

class PathBasedRoutingMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var PathBasedRoutingMiddlewareFactory */
    private $factory;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new PathBasedRoutingMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfRouterServiceIsMissing()
    {
        $this->container->has(RouterInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Router\RouterInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesPathBasedRoutingMiddlewareWhenAllDependenciesPresent()
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->get(RouterInterface::class)->willReturn($router);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(PathBasedRoutingMiddleware::class, $middleware);
    }
}
