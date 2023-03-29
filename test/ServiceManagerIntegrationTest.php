<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Laminas\Diactoros\StreamFactory;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function array_merge_recursive;

/** @psalm-import-type ServiceManagerConfigurationType from ConfigInterface */
final class ServiceManagerIntegrationTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        /** @psalm-var ServiceManagerConfigurationType $config */
        $config          = array_merge_recursive(
            (new Router\ConfigProvider())->getDependencies(),
            [
                'factories' => [
                    ResponseFactoryInterface::class => function (): ResponseFactoryInterface {
                        return new Router\Test\ResponseFactory();
                    },
                    Router\RouterInterface::class   => function (): Router\RouterInterface {
                        return $this->createMock(Router\RouterInterface::class);
                    },
                    StreamFactoryInterface::class   => function (): StreamFactoryInterface {
                        return new StreamFactory();
                    },
                ],
            ],
        );
        $this->container = new ServiceManager($config);
    }

    /**
     * A list of container ids that should resolve to the expected type
     *
     * @return array<array-key, array{0: class-string, 1: class-string}>
     */
    public static function factories(): array
    {
        return [
            [Router\Middleware\DispatchMiddleware::class, Router\Middleware\DispatchMiddleware::class],
            [Router\Middleware\ImplicitHeadMiddleware::class, Router\Middleware\ImplicitHeadMiddleware::class],
            [Router\Middleware\ImplicitOptionsMiddleware::class, Router\Middleware\ImplicitOptionsMiddleware::class],
            [Router\Middleware\MethodNotAllowedMiddleware::class, Router\Middleware\MethodNotAllowedMiddleware::class],
            [Router\Middleware\RouteMiddleware::class, Router\Middleware\RouteMiddleware::class],
            [Router\RouteCollector::class, Router\RouteCollector::class],
        ];
    }

    /**
     * A list of container ids that should resolve to the expected type
     *
     * @return array<array-key, array{0: class-string, 1: class-string}>
     */
    public static function aliases(): array
    {
        return [
            [Router\RouteCollectorInterface::class, Router\RouteCollectorInterface::class],
        ];
    }

    /**
     * @param class-string $id
     * @param class-string $expectedType
     */
    #[DataProvider('factories')]
    public function testFactoryIdentifiersCanBeRetrievedFromTheContainer(string $id, string $expectedType): void
    {
        self::assertTrue($this->container->has($id));
        $instance = $this->container->get($id);
        self::assertInstanceOf($expectedType, $instance);
    }

    /**
     * @param class-string $id
     * @param class-string $expectedType
     */
    #[DataProvider('aliases')]
    public function testAliasesCanBeRetrievedFromTheContainer(string $id, string $expectedType): void
    {
        self::assertTrue($this->container->has($id));
        $instance = $this->container->get($id);
        self::assertInstanceOf($expectedType, $instance);
    }
}
