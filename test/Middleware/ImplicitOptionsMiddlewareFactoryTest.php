<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

use const Mezzio\Router\IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE;

class ImplicitOptionsMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var ImplicitOptionsMiddlewareFactory */
    private $factory;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new ImplicitOptionsMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfResponseServiceIsMissing()
    {
        $this->container->has(IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE)->willReturn(false);
        $this->container->has(\const Zend\Expressive\Router\IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesImplicitOptionsMiddlewareWhenAllDependenciesPresent()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->container->has(IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE)->willReturn(true);
        $this->container->get(IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE)->willReturn($response);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(ImplicitOptionsMiddleware::class, $middleware);
    }
}
