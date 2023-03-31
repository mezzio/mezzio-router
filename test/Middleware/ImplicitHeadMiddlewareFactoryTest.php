<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Laminas\Diactoros\StreamFactory;
use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\ImplicitHeadMiddlewareFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

use function in_array;

#[CoversClass(ImplicitHeadMiddlewareFactory::class)]
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
            ->expects(self::once())
            ->method('has')
            ->with(RouterInterface::class)
            ->willReturn(false);

        $this->expectException(MissingDependencyException::class);

        ($this->factory)($this->container);
    }

    public function testFactoryRaisesExceptionIfStreamFactoryServiceIsMissing(): void
    {
        $this->container
            ->expects(self::exactly(3))
            ->method('has')
            ->with(self::callback(function (string $arg): bool {
                self::assertTrue(in_array($arg, [
                    RouterInterface::class,
                    StreamFactoryInterface::class,
                    StreamInterface::class,
                ]));
                return true;
            }))
            ->willReturnOnConsecutiveCalls(true, false, false);

        $this->expectException(MissingDependencyException::class);

        ($this->factory)($this->container);
    }

    public function testFactoryProducesImplicitHeadMiddlewareWithCallableStreamFactory(): void
    {
        $router        = $this->createMock(RouterInterface::class);
        $streamFactory = static function (): void {
        };

        $this->container
            ->expects(self::exactly(3))
            ->method('has')
            ->with(self::callback(function (string $arg): bool {
                self::assertTrue(in_array($arg, [
                    RouterInterface::class,
                    StreamFactoryInterface::class,
                    StreamInterface::class,
                ]));
                return true;
            }))
            ->willReturnOnConsecutiveCalls(true, false, true);

        $this->container
            ->expects(self::exactly(2))
            ->method('get')
            ->with(self::callback(function (string $arg): bool {
                self::assertTrue(in_array($arg, [RouterInterface::class, StreamInterface::class]));
                return true;
            }))
            ->willReturnOnConsecutiveCalls($router, $streamFactory);

        ($this->factory)($this->container);
    }

    public function testFactoryProducesImplicitHeadMiddlewareWithStreamFactoryInterface(): void
    {
        $router        = $this->createMock(RouterInterface::class);
        $streamFactory = new StreamFactory();

        $this->container
            ->expects(self::exactly(3))
            ->method('has')
            ->with(self::callback(function (string $arg): bool {
                self::assertTrue(in_array($arg, [
                    RouterInterface::class,
                    StreamFactoryInterface::class,
                    StreamInterface::class,
                ]));
                return true;
            }))
            ->willReturn(true, true, false);

        $this->container
            ->expects(self::exactly(2))
            ->method('get')
            ->with(self::callback(function (string $arg): bool {
                self::assertTrue(in_array($arg, [RouterInterface::class, StreamFactoryInterface::class]));
                return true;
            }))
            ->willReturnOnConsecutiveCalls($router, $streamFactory);

        ($this->factory)($this->container);
    }
}
