<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteCollectorFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface as ZendExpressiveRouterInterface;

use function sprintf;

class RouteCollectorFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private $container;

    /** @var RouteCollectorFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new RouteCollectorFactory();
    }

    public function testFactoryRaisesExceptionIfRouterServiceIsMissing(): void
    {
        $this->container
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([RouterInterface::class], [ZendExpressiveRouterInterface::class])
            ->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage(RouteCollector::class);
        ($this->factory)($this->container);
    }

    public function testFactoryProducesRouteCollectorWhenAllDependenciesPresent(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $this->container
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([RouterInterface::class], ['config'])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with(RouterInterface::class)
            ->willReturn($router);

        $collector = ($this->factory)($this->container);

        self::assertTrue($collector->willDetectDuplicates());
    }

    public function testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromArrayConfig(): void
    {
        $this->testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromConfig([
            RouteCollector::class => [
                'detect_duplicates' => false,
            ],
        ]);
    }

    public function testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromArrayObjectConfig(): void
    {
        $this->testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromConfig(new ArrayObject([
            RouteCollector::class => [
                'detect_duplicates' => false,
            ],
        ]));
    }

    public function testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromArrayIteratorConfig(): void
    {
        $this->testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromConfig(new ArrayIterator([
            RouteCollector::class => [
                'detect_duplicates' => false,
            ],
        ]));
    }

    public function testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromAnyObjectConfig(): void
    {
        $this->expectExceptionMessage(sprintf('Config must be an array or implement %s.', ArrayAccess::class));

        $this->testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromConfig(new class {
            // custom properties
        });
    }

    /**
     * @param mixed $config
     */
    private function testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromConfig($config): void
    {
        $router = $this->createMock(RouterInterface::class);
        $this->container
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive(
                [RouterInterface::class],
                ['config']
            )
            ->willReturn(true);

        $this->container
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive([RouterInterface::class], ['config'])
            ->willReturnOnConsecutiveCalls($router, $config);

        $collector = ($this->factory)($this->container);

        self::assertFalse($collector->willDetectDuplicates());
    }
}
