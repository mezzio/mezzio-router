<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\MethodNotAllowedMiddlewareFactory;
use Mezzio\Router\Test\ResponseFactory;
use MezzioTest\Router\InMemoryContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

#[CoversClass(MethodNotAllowedMiddlewareFactory::class)]
final class MethodNotAllowedMiddlewareFactoryTest extends TestCase
{
    private static ResponseFactoryInterface&MockObject $responseFactoryMock;
    private ContainerInterface&MockObject $container;
    private MethodNotAllowedMiddlewareFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new MethodNotAllowedMiddlewareFactory();

        self::$responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
    }

    public function testFactoryRaisesExceptionIfResponseFactoryServiceIsMissing(): void
    {
        $this->container
            ->expects(self::once())
            ->method('has')
            ->with(ResponseFactoryInterface::class)
            ->willReturn(false);

        $this->expectException(MissingDependencyException::class);

        ($this->factory)($this->container);
    }

    public function testFactoryProducesMethodNotAllowedMiddlewareWhenAllDependenciesPresent(): void
    {
        $this->container
            ->expects(self::once())
            ->method('has')
            ->with(ResponseFactoryInterface::class)
            ->willReturn(true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with(ResponseFactoryInterface::class)
            ->willReturn(new ResponseFactory());

        ($this->factory)($this->container);
    }

    public function testWillUseResponseFactoryInterfaceFromContainer(): void
    {
        $responseFactory = new ResponseFactory();
        $container       = new InMemoryContainer();
        $container->set(ResponseFactoryInterface::class, $responseFactory);
        $middleware = ($this->factory)($container);

        self::assertSame($responseFactory, $middleware->getResponseFactory());
    }
}
