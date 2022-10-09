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

/** @covers \Mezzio\Router\Middleware\ImplicitHeadMiddlewareFactory */
final class ImplicitHeadMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private ImplicitHeadMiddlewareFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new ImplicitHeadMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfRouterInterfaceServiceIsMissing(): void
    {
        $this->container
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([RouterInterface::class], [ZendExpressiveRouterInterface::class])
            ->willReturn(false);

        $this->expectException(MissingDependencyException::class);

        ($this->factory)($this->container);
    }

    public function testFactoryRaisesExceptionIfStreamFactoryServiceIsMissing(): void
    {
        $this->container
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([RouterInterface::class], [StreamInterface::class])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->expectException(MissingDependencyException::class);

        ($this->factory)($this->container);
    }

    public function testFactoryProducesImplicitHeadMiddlewareWhenAllDependenciesPresent(): void
    {
        $router        = $this->createMock(RouterInterface::class);
        $streamFactory = static function (): void {
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
            ->willReturn($router, $streamFactory);

        ($this->factory)($this->container);
    }
}
