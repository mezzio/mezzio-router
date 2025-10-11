<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Laminas\Diactoros\StreamFactory;
use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\ImplicitHeadMiddlewareFactory;
use Mezzio\Router\RouterInterface;
use MezzioTest\Router\InMemoryContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;

#[CoversClass(ImplicitHeadMiddlewareFactory::class)]
final class ImplicitHeadMiddlewareFactoryTest extends TestCase
{
    private InMemoryContainer $container;
    private ImplicitHeadMiddlewareFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new InMemoryContainer();
        $this->factory   = new ImplicitHeadMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfRouterInterfaceServiceIsMissing(): void
    {
        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage(RouterInterface::class);

        ($this->factory)($this->container);
    }

    public function testFactoryRaisesExceptionIfStreamFactoryInterfaceServiceIsMissing(): void
    {
        $this->container->set(RouterInterface::class, $this->createMock(RouterInterface::class));

        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage(StreamFactoryInterface::class);

        ($this->factory)($this->container);
    }

    public function testFactoryProducesImplicitHeadMiddlewareWithStreamFactoryInterface(): void
    {
        $this->container->set(RouterInterface::class, $this->createMock(RouterInterface::class));
        $this->container->set(StreamFactoryInterface::class, new StreamFactory());
        ($this->factory)($this->container);

        $this->expectNotToPerformAssertions();
    }
}
