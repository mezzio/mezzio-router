<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\ImplicitOptionsMiddlewareFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(ImplicitOptionsMiddlewareFactory::class)]
final class ImplicitOptionsMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private ImplicitOptionsMiddlewareFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new ImplicitOptionsMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfResponseFactoryServiceIsMissing(): void
    {
        $this->expectException(MissingDependencyException::class);

        ($this->factory)($this->container);
    }

    public function testFactoryProducesImplicitOptionsMiddlewareWhenAllDependenciesPresent(): void
    {
        $factory = static function (): void {
        };

        $this->container
            ->method('has')
            ->with(self::callback(static function ($arg): bool {
                self::assertContains($arg, [
                    ResponseFactoryInterface::class,
                    ResponseInterface::class,
                ]);

                return true;
            }))->willReturnOnConsecutiveCalls(false, true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with(ResponseInterface::class)
            ->willReturn($factory);

        ($this->factory)($this->container);
    }
}
