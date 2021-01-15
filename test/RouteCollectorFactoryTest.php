<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteCollectorFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Zend\Expressive\Router\RouterInterface as ZendExpressiveRouterInterface;

use function sprintf;

class RouteCollectorFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var RouteCollectorFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory   = new RouteCollectorFactory();
    }

    public function testFactoryRaisesExceptionIfRouterServiceIsMissing(): void
    {
        $this->container->has(RouterInterface::class)->willReturn(false);
        $this->container->has(ZendExpressiveRouterInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage(RouteCollector::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesRouteCollectorWhenAllDependenciesPresent(): void
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->has('config')->willReturn(false);
        $this->container->get(RouterInterface::class)->willReturn($router);

        $collector = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(RouteCollector::class, $collector);

        $r = new ReflectionProperty($collector, 'detectDuplicates');
        $r->setAccessible(true);

        $this->assertTrue($r->getValue($collector));
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
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->has('config')->willReturn(true);
        $this->container->get(RouterInterface::class)->willReturn($router);
        $this->container->get('config')->willReturn($config);

        $collector = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(RouteCollector::class, $collector);

        $r = new ReflectionProperty($collector, 'detectDuplicates');
        $r->setAccessible(true);

        $this->assertFalse($r->getValue($collector));
    }
}
