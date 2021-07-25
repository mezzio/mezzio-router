<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Exception;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TypeError;

use function array_keys;
use function microtime;
use function range;
use function sprintf;

class RouteCollectorTest extends TestCase
{
    /** @var RouterInterface&MockObject */
    private $router;

    /** @var ResponseInterface&MockObject */
    private $response;

    /** @var RouteCollector */
    private $collector;

    /** @var MiddlewareInterface */
    private $noopMiddleware;

    protected function setUp(): void
    {
        $this->router         = $this->createMock(RouterInterface::class);
        $this->response       = $this->createMock(ResponseInterface::class);
        $this->collector      = new RouteCollector($this->router);
        $this->noopMiddleware = $this->createNoopMiddleware();
    }

    public function createNoopMiddleware(): MiddlewareInterface
    {
        return new class ($this->response) implements MiddlewareInterface {
            /** @var ResponseInterface */
            private $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $this->response;
            }
        };
    }

    /**
     * @return string[][]
     * @psalm-return array<array-key, array{0:string}>
     */
    public function commonHttpMethods(): array
    {
        return [
            RequestMethod::METHOD_GET    => [RequestMethod::METHOD_GET],
            RequestMethod::METHOD_POST   => [RequestMethod::METHOD_POST],
            RequestMethod::METHOD_PUT    => [RequestMethod::METHOD_PUT],
            RequestMethod::METHOD_PATCH  => [RequestMethod::METHOD_PATCH],
            RequestMethod::METHOD_DELETE => [RequestMethod::METHOD_DELETE],
        ];
    }

    public function testRouteMethodReturnsRouteInstance(): void
    {
        $this->router
            ->expects(self::once())
            ->method('addRoute')
            ->willReturnArgument(0);

        $route = $this->collector->route('/foo', $this->noopMiddleware);
        self::assertEquals('/foo', $route->getPath());
        self::assertSame($this->noopMiddleware, $route->getMiddleware());
    }

    public function testAnyRouteMethod(): void
    {
        $this->router
            ->expects(self::once())
            ->method('addRoute')
            ->willReturnArgument(0);

        $route = $this->collector->any('/foo', $this->noopMiddleware);
        self::assertEquals('/foo', $route->getPath());
        self::assertSame($this->noopMiddleware, $route->getMiddleware());
        self::assertSame(Route::HTTP_METHOD_ANY, $route->getAllowedMethods());
    }

    /**
     * @dataProvider commonHttpMethods
     * @param string $method
     */
    public function testCanCallRouteWithHttpMethods($method): void
    {
        $this->router
            ->expects(self::once())
            ->method('addRoute')
            ->willReturnArgument(0);

        $route = $this->collector->route('/foo', $this->noopMiddleware, [$method]);
        self::assertEquals('/foo', $route->getPath());
        self::assertSame($this->noopMiddleware, $route->getMiddleware());
        self::assertTrue($route->allowsMethod($method));
        self::assertSame([$method], $route->getAllowedMethods());
    }

    public function testCanCallRouteWithMultipleHttpMethods(): void
    {
        $this->router
            ->expects(self::once())
            ->method('addRoute')
            ->willReturnArgument(0);

        $methods = array_keys($this->commonHttpMethods());
        $route   = $this->collector->route('/foo', $this->noopMiddleware, $methods);
        self::assertEquals('/foo', $route->getPath());
        self::assertSame($this->noopMiddleware, $route->getMiddleware());
        self::assertSame($methods, $route->getAllowedMethods());
    }

    public function testCallingRouteWithExistingPathAndOmittingMethodsArgumentRaisesException(): void
    {
        $this->router
            ->expects(self::exactly(2))
            ->method('addRoute')
            ->willReturnArgument(0);

        $this->collector->route('/foo', $this->noopMiddleware);
        $this->collector->route('/bar', $this->noopMiddleware);
        $this->expectException(Exception\DuplicateRouteException::class);
        $this->collector->route('/foo', $this->createNoopMiddleware());
    }

    /**
     * @return mixed[]
     */
    public function invalidPathTypes(): array
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['path' => 'route']],
            'object'     => [(object) ['path' => 'route']],
        ];
    }

    /**
     * @dataProvider invalidPathTypes
     * @param mixed $path
     */
    public function testCallingRouteWithAnInvalidPathTypeRaisesAnException($path): void
    {
        $this->expectException(TypeError::class);
        $this->collector->route($path, $this->createNoopMiddleware());
    }

    /**
     * @dataProvider commonHttpMethods
     * @param mixed $method
     */
    public function testCommonHttpMethodsAreExposedAsClassMethodsAndReturnRoutes($method): void
    {
        $route = $this->collector->{$method}('/foo', $this->noopMiddleware);
        self::assertInstanceOf(Route::class, $route);
        self::assertEquals('/foo', $route->getPath());
        self::assertSame($this->noopMiddleware, $route->getMiddleware());
        self::assertEquals([$method], $route->getAllowedMethods());
    }

    public function testCreatingHttpRouteMethodWithExistingPathButDifferentMethodCreatesNewRouteInstance(): void
    {
        $this->router
            ->expects(self::exactly(2))
            ->method('addRoute')
            ->willReturnArgument(0);

        $route = $this->collector->route('/foo', $this->noopMiddleware, [RequestMethod::METHOD_POST]);

        $middleware = $this->createNoopMiddleware();
        $test       = $this->collector->get('/foo', $middleware);
        self::assertNotSame($route, $test);
        self::assertSame($route->getPath(), $test->getPath());
        self::assertSame(['GET'], $test->getAllowedMethods());
        self::assertSame($middleware, $test->getMiddleware());
    }

    public function testGetRoutes(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $this->collector->any('/foo', $middleware1, 'abc');
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $this->collector->get('/bar', $middleware2, 'def');

        $routes = $this->collector->getRoutes();

        self::assertCount(2, $routes);
        self::assertContainsOnlyInstancesOf(Route::class, $routes);

        self::assertSame('/foo', $routes[0]->getPath());
        self::assertSame($middleware1, $routes[0]->getMiddleware());
        self::assertSame('abc', $routes[0]->getName());
        self::assertNull($routes[0]->getAllowedMethods());

        self::assertSame('/bar', $routes[1]->getPath());
        self::assertSame($middleware2, $routes[1]->getMiddleware());
        self::assertSame('def', $routes[1]->getName());
        self::assertSame([RequestMethod::METHOD_GET], $routes[1]->getAllowedMethods());
    }

    public function testCreatingHttpRouteWithExistingPathShouldBeLinear(): void
    {
        $start = microtime(true);
        foreach (range(1, 10) as $item) {
            $this->collector->get("/bar$item", $this->noopMiddleware);
        }
        $baseDuration    = microtime(true) - $start;
        $this->collector = new RouteCollector($this->router);

        $start = microtime(true);
        foreach (range(1, 10000) as $item) {
            $this->collector->get("/foo$item", $this->noopMiddleware);
        }

        $duration         = microtime(true) - $start;
        $expectedDuration = $baseDuration * 1000;
        $error            = 30 * $expectedDuration / 100;
        self::assertTrue(
            $expectedDuration + $error > $duration,
            sprintf(
                'Route add time should be linear by amount of routes,'
                . ' expected duration: %s, possible error: %s, actual duration: %s',
                $expectedDuration,
                $error,
                $duration
            )
        );
    }

    public function testCreatingHttpRouteWithExistingPathAndMethodRaisesException(): void
    {
        $this->router
            ->expects(self::once())
            ->method('addRoute')
            ->willReturnArgument(0);

        $this->collector->get('/foo', $this->noopMiddleware, 'route1');

        $this->expectException(Exception\DuplicateRouteException::class);
        $message = 'Duplicate route detected; path "/foo" answering to methods [GET], with name "route2"';
        $this->expectExceptionMessage($message);

        $this->collector->get('/foo', $this->createNoopMiddleware(), 'route2');
    }

    public function testCheckDuplicateRouteWhenExistsRouteForAnyMethods(): void
    {
        $this->router
            ->expects(self::once())
            ->method('addRoute')
            ->willReturnArgument(0);

        $this->collector->any('/foo', $this->noopMiddleware, 'route1');

        $this->expectException(Exception\DuplicateRouteException::class);
        $message = 'Duplicate route detected; path "/foo" answering to methods [GET], with name "route2"';
        $this->expectExceptionMessage($message);

        $this->collector->get('/foo', $this->createNoopMiddleware(), 'route2');
    }

    public function testCheckDuplicateRouteWhenExistsRouteForGetMethodsAndAddingRouteForAnyMethod(): void
    {
        $this->router
            ->expects(self::once())
            ->method('addRoute')
            ->willReturnArgument(0);

        $this->collector->get('/foo', $this->noopMiddleware, 'route1');

        $this->expectException(Exception\DuplicateRouteException::class);
        $message = 'Duplicate route detected; path "/foo" answering to methods [(any)], with name "route2"';
        $this->expectExceptionMessage($message);

        $this->collector->any('/foo', $this->createNoopMiddleware(), 'route2');
    }

    public function testCreatingHttpRouteWithExistingNameRaisesException(): void
    {
        $this->router
            ->expects(self::once())
            ->method('addRoute')
            ->willReturnArgument(0);

        $this->collector->get('/foo', $this->noopMiddleware, 'duplicate');

        $this->expectException(Exception\DuplicateRouteException::class);
        $message = 'Duplicate route detected; path "/foo/baz" answering to methods [GET], with name "duplicate"';
        $this->expectExceptionMessage($message);

        $this->collector->get('/foo/baz', $this->createNoopMiddleware(), 'duplicate');
    }

    public function testCreatingHttpRouteWithExistingNameDoesNotRaiseExceptionIfDuplicateDetectionDisabled(): void
    {
        $this->router
            ->expects(self::exactly(2))
            ->method('addRoute')
            ->willReturnArgument(0);

        $collector = new RouteCollector($this->router, false);
        $collector->get('/foo', $this->noopMiddleware, 'duplicate');
        $collector->get('/foo/baz', $this->createNoopMiddleware(), 'duplicate');

        self::assertCount(2, $collector->getRoutes());
    }
}
