<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

use const Mezzio\Router\IMPLICIT_HEAD_MIDDLEWARE_RESPONSE;
use const Mezzio\Router\IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY;

class ImplicitHeadMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var ImplicitHeadMiddlewareFactory */
    private $factory;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new ImplicitHeadMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfResponseServiceIsMissing()
    {
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)->willReturn(false);
        $this->container->has(\const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_RESPONSE::class)->willReturn(false);
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)->shouldNotBeCalled();
        $this->container->has(\const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY::class)->shouldNotBeCalled();

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfStreamFactoryServiceIsMissing()
    {
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)->willReturn(true);
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)->willReturn(false);
        $this->container->has(\const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesImplicitHeadMiddlewareWhenAllDependenciesPresent()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $factory = function () {
        };

        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)->willReturn(true);
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)->willReturn(true);

        $this->container->get(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)->willReturn($response);
        $this->container->get(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)->willReturn($factory);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(ImplicitHeadMiddleware::class, $middleware);
    }
}
