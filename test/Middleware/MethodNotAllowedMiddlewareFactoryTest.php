<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Generator;
use Mezzio\Container\ResponseFactoryFactory;
use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\Middleware\MethodNotAllowedMiddlewareFactory;
use Mezzio\Router\Response\CallableResponseFactoryDecorator;
use MezzioTest\Router\InMemoryContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class MethodNotAllowedMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private $container;

    /** @var MethodNotAllowedMiddlewareFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new MethodNotAllowedMiddlewareFactory();
    }

    /**
     * @psalm-return Generator<non-empty-string,array{0:array<string,mixed>}>
     */
    public function configurationsWithOverriddenResponseInterfaceFactory(): Generator
    {
        yield 'default' => [
            [
                'dependencies' => [
                    'factories' => [
                        ResponseInterface::class => function (): ResponseInterface {
                            return $this->createMock(ResponseInterface::class);
                        },
                    ],
                ],
            ],
        ];

        yield 'aliased' => [
            [
                'dependencies' => [
                    'aliases' => [
                        ResponseInterface::class => 'CustomResponseInterface',
                    ],
                ],
            ],
        ];

        yield 'delegated' => [
            [
                'dependencies' => [
                    'delegators' => [
                        ResponseInterface::class => [
                            function (): ResponseInterface {
                                return $this->createMock(ResponseInterface::class);
                            },
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testFactoryRaisesExceptionIfResponseFactoryServiceIsMissing(): void
    {
        $this->container
            ->method('has')
            ->withConsecutive([ResponseFactoryInterface::class], [ResponseInterface::class])
            ->willReturn(false);
        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container);
    }

    public function testFactoryProducesMethodNotAllowedMiddlewareWhenAllDependenciesPresent(): void
    {
        $factory = function (): void {
        };

        $this->container
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([ResponseFactoryInterface::class], [ResponseInterface::class])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with(ResponseInterface::class)
            ->willReturn($factory);

        ($this->factory)($this->container);
    }

    public function testWillUseResponseFactoryInterfaceFromContainerWhenApplicationFactoryIsNotOverridden(): void
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $container       = new InMemoryContainer();
        $container->set('config', [
            'dependencies' => [
                'factories' => [
                    ResponseInterface::class => ResponseFactoryFactory::class,
                ],
            ],
        ]);
        $container->set(ResponseFactoryInterface::class, $responseFactory);
        $middleware = ($this->factory)($container);
        self::assertSame($responseFactory, $middleware->getResponseFactory());
    }

    /**
     * @param array<string,mixed> $config
     * @dataProvider configurationsWithOverriddenResponseInterfaceFactory
     */
    public function testWontUseResponseFactoryInterfaceFromContainerWhenApplicationFactoryIsOverriden(
        array $config
    ): void {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $container       = new InMemoryContainer();
        $container->set('config', $config);
        $container->set(ResponseFactoryInterface::class, $responseFactory);
        $response = $this->createMock(ResponseInterface::class);
        $container->set(ResponseInterface::class, function () use ($response): ResponseInterface {
            return $response;
        });

        $middleware                   = ($this->factory)($container);
        $responseFactoryFromGenerator = $middleware->getResponseFactory();
        self::assertNotSame($responseFactory, $responseFactoryFromGenerator);
        self::assertInstanceOf(CallableResponseFactoryDecorator::class, $responseFactoryFromGenerator);
        self::assertEquals($response, $responseFactoryFromGenerator->getResponseFromCallable());
    }
}
