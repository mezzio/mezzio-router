<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Exception;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
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
    /** @var RouterInterface|ObjectProphecy */
    private $router;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    /** @var RouteCollector */
    private $collector;

    /** @var MiddlewareInterface */
    private $noopMiddleware;

    protected function setUp(): void
    {
        $this->router         = $this->prophesize(RouterInterface::class);
        $this->response       = $this->prophesize(ResponseInterface::class);
        $this->collector      = new RouteCollector($this->router->reveal());
        $this->noopMiddleware = $this->createNoopMiddleware();
    }

    public function createNoopMiddleware(): MiddlewareInterface
    {
        return new class ($this->response->reveal()) implements MiddlewareInterface {
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
     *
     * @psalm-return array{GET: array{0: string}, POST: array{0: string}, PUT: array{0: string}, PATCH: array{0: string}, DELETE: array{0: string}}
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
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $route = $this->collector->route('/foo', $this->noopMiddleware);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
    }

    public function testAnyRouteMethod(): void
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $route = $this->collector->any('/foo', $this->noopMiddleware);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertSame(Route::HTTP_METHOD_ANY, $route->getAllowedMethods());
    }

    /**
     * @dataProvider commonHttpMethods
     *
     * @param string $method
     *
     * @return void
     */
    public function testCanCallRouteWithHttpMethods($method): void
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $route = $this->collector->route('/foo', $this->noopMiddleware, [$method]);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertTrue($route->allowsMethod($method));
        $this->assertSame([$method], $route->getAllowedMethods());
    }

    public function testCanCallRouteWithMultipleHttpMethods(): void
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $methods = array_keys($this->commonHttpMethods());
        $route   = $this->collector->route('/foo', $this->noopMiddleware, $methods);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertSame($methods, $route->getAllowedMethods());
    }

    public function testCallingRouteWithExistingPathAndOmittingMethodsArgumentRaisesException(): void
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(2);
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
     *
     * @param mixed $path
     *
     * @return void
     */
    public function testCallingRouteWithAnInvalidPathTypeRaisesAnException($path): void
    {
        $this->expectException(TypeError::class);
        $this->collector->route($path, $this->createNoopMiddleware());
    }

    /**
     * @dataProvider commonHttpMethods
     *
     * @param mixed $method
     *
     * @return void
     */
    public function testCommonHttpMethodsAreExposedAsClassMethodsAndReturnRoutes($method): void
    {
        $route = $this->collector->{$method}('/foo', $this->noopMiddleware);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertEquals([$method], $route->getAllowedMethods());
    }

    public function testCreatingHttpRouteMethodWithExistingPathButDifferentMethodCreatesNewRouteInstance(): void
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(2);
        $route = $this->collector->route('/foo', $this->noopMiddleware, [RequestMethod::METHOD_POST]);

        $middleware = $this->createNoopMiddleware();
        $test       = $this->collector->get('/foo', $middleware);
        $this->assertNotSame($route, $test);
        $this->assertSame($route->getPath(), $test->getPath());
        $this->assertSame(['GET'], $test->getAllowedMethods());
        $this->assertSame($middleware, $test->getMiddleware());
    }

    public function testGetRoutes(): void
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $this->collector->any('/foo', $middleware1, 'abc');
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $this->collector->get('/bar', $middleware2, 'def');

        $routes = $this->collector->getRoutes();

        $this->assertIsArray($routes);
        $this->assertCount(2, $routes);
        $this->assertContainsOnlyInstancesOf(Route::class, $routes);

        $this->assertSame('/foo', $routes[0]->getPath());
        $this->assertSame($middleware1, $routes[0]->getMiddleware());
        $this->assertSame('abc', $routes[0]->getName());
        $this->assertNull($routes[0]->getAllowedMethods());

        $this->assertSame('/bar', $routes[1]->getPath());
        $this->assertSame($middleware2, $routes[1]->getMiddleware());
        $this->assertSame('def', $routes[1]->getName());
        $this->assertSame([RequestMethod::METHOD_GET], $routes[1]->getAllowedMethods());
    }

    public function testCreatingHttpRouteWithExistingPathShouldBeLinear(): void
    {
        $start = microtime(true);
        foreach (range(1, 10) as $item) {
            $this->collector->get("/bar$item", $this->noopMiddleware);
        }
        $baseDuration    = microtime(true) - $start;
        $this->collector = new RouteCollector($this->router->reveal());

        $start = microtime(true);
        foreach (range(1, 10000) as $item) {
            $this->collector->get("/foo$item", $this->noopMiddleware);
        }

        $duration         = microtime(true) - $start;
        $expectedDuration = $baseDuration * 1000;
        $error            = 30 * $expectedDuration / 100;
        $this->assertTrue(
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
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(1);
        $this->collector->get('/foo', $this->noopMiddleware, 'route1');

        $this->expectException(Exception\DuplicateRouteException::class);
        $message = 'Duplicate route detected; path "/foo" answering to methods [GET], with name "route2"';
        $this->expectExceptionMessage($message);

        $this->collector->get('/foo', $this->createNoopMiddleware(), 'route2');
    }

    public function testCheckDuplicateRouteWhenExistsRouteForAnyMethods(): void
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(1);
        $this->collector->any('/foo', $this->noopMiddleware, 'route1');

        $this->expectException(Exception\DuplicateRouteException::class);
        $message = 'Duplicate route detected; path "/foo" answering to methods [GET], with name "route2"';
        $this->expectExceptionMessage($message);

        $this->collector->get('/foo', $this->createNoopMiddleware(), 'route2');
    }

    public function testCheckDuplicateRouteWhenExistsRouteForGetMethodsAndAddingRouteForAnyMethod(): void
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(1);
        $this->collector->get('/foo', $this->noopMiddleware, 'route1');

        $this->expectException(Exception\DuplicateRouteException::class);
        $message = 'Duplicate route detected; path "/foo" answering to methods [(any)], with name "route2"';
        $this->expectExceptionMessage($message);

        $this->collector->any('/foo', $this->createNoopMiddleware(), 'route2');
    }

    public function testCreatingHttpRouteWithExistingNameRaisesException(): void
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(1);
        $this->collector->get('/foo', $this->noopMiddleware, 'duplicate');

        $this->expectException(Exception\DuplicateRouteException::class);
        $message = 'Duplicate route detected; path "/foo/baz" answering to methods [GET], with name "duplicate"';
        $this->expectExceptionMessage($message);

        $this->collector->get('/foo/baz', $this->createNoopMiddleware(), 'duplicate');
    }

    public function testCreatingHttpRouteWithExistingNameDoesNotRaiseExceptionIfDuplicateDetectionDisabled(): void
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(2);
        $collector = new RouteCollector($this->router->reveal(), false);
        $collector->get('/foo', $this->noopMiddleware, 'duplicate');
        $collector->get('/foo/baz', $this->createNoopMiddleware(), 'duplicate');

        $this->assertCount(2, $collector->getRoutes());
    }
}
