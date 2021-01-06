<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class MethodNotAllowedMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var MethodNotAllowedMiddlewareFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory   = new MethodNotAllowedMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfResponseFactoryServiceIsMissing(): void
    {
        $this->container->has(ResponseInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesMethodNotAllowedMiddlewareWhenAllDependenciesPresent(): void
    {
        $factory = function (): void {
        };

        $this->container->has(ResponseInterface::class)->willReturn(true);
        $this->container->get(ResponseInterface::class)->willReturn($factory);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(MethodNotAllowedMiddleware::class, $middleware);
    }
}
