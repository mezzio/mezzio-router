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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(RouteCollectorFactory::class)]
final class RouteCollectorFactoryTest extends TestCase
{
    private InMemoryContainer $container;
    private RouteCollectorFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new InMemoryContainer();
        $this->factory   = new RouteCollectorFactory();
    }

    public function testFactoryRaisesExceptionIfRouterServiceIsMissing(): void
    {
        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage(RouteCollector::class);

        ($this->factory)($this->container);
    }

    public function testFactoryProducesRouteCollectorWhenAllDependenciesPresent(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $this->container->set(RouterInterface::class, $router);
        $collector = ($this->factory)($this->container);

        self::assertTrue($collector->willDetectDuplicates());
    }

    public function testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromArrayConfig(): void
    {
        $this->testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromConfig([
            'router' => [
                'detect_duplicates' => false,
            ],
        ]);
    }

    public function testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromArrayObjectConfig(): void
    {
        $this->testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromConfig(new ArrayObject([
            'router' => [
                'detect_duplicates' => false,
            ],
        ]));
    }

    public function testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromArrayIteratorConfig(): void
    {
        $this->testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromConfig(new ArrayIterator([
            'router' => [
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

    private function testFactoryProducesRouteCollectorUsingDetectDuplicatesFlagFromConfig(mixed $config): void
    {
        $router = $this->createMock(RouterInterface::class);
        $this->container->set(RouterInterface::class, $router);
        $this->container->set('config', $config);

        $collector = ($this->factory)($this->container);

        self::assertFalse($collector->willDetectDuplicates());
    }
}
