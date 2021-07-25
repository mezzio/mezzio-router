<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\ImplicitHeadMiddlewareFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Expressive\Router\RouterInterface as ZendExpressiveRouterInterface;

class ImplicitHeadMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private $container;

    /** @var ImplicitHeadMiddlewareFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new ImplicitHeadMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfRouterInterfaceServiceIsMissing(): void
    {
        $this->container
            ->method('has')
            ->withConsecutive([RouterInterface::class], [ZendExpressiveRouterInterface::class])
            ->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container);
    }

    public function testFactoryRaisesExceptionIfStreamFactoryServiceIsMissing(): void
    {
        $this->container
            ->method('has')
            ->withConsecutive([RouterInterface::class], [StreamInterface::class])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container);
    }

    public function testFactoryProducesImplicitHeadMiddlewareWhenAllDependenciesPresent(): void
    {
        $router        = $this->createMock(RouterInterface::class);
        $streamFactory = function (): void {
        };

        $this->container
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([RouterInterface::class], [StreamInterface::class])
            ->willReturn(true);

        $this->container
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive([RouterInterface::class], [StreamInterface::class])
            ->willReturnOnConsecutiveCalls($router, $streamFactory);

        ($this->factory)($this->container);
    }
}
