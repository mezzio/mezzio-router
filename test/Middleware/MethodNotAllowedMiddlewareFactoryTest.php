<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Laminas\Diactoros\ResponseFactory;
use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\MethodNotAllowedMiddlewareFactory;
use MezzioTest\Router\InMemoryContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;

#[CoversClass(MethodNotAllowedMiddlewareFactory::class)]
final class MethodNotAllowedMiddlewareFactoryTest extends TestCase
{
    private InMemoryContainer $container;
    private MethodNotAllowedMiddlewareFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new InMemoryContainer();
        $this->factory   = new MethodNotAllowedMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfResponseFactoryServiceIsMissing(): void
    {
        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage(ResponseFactoryInterface::class);

        ($this->factory)($this->container);
    }

    public function testFactoryProducesMethodNotAllowedMiddlewareWhenAllDependenciesPresent(): void
    {
        $this->container->set(ResponseFactoryInterface::class, new ResponseFactory());
        ($this->factory)($this->container);

        $this->expectNotToPerformAssertions();
    }
}
