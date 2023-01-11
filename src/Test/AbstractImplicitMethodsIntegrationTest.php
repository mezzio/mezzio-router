<?php

declare(strict_types=1);

namespace Mezzio\Router\Test;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Generator;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function implode;

/**
 * Base class for testing adapter integrations.
 *
 * Implementers of adapters should extend this class in their test suite,
 * implementing the `getRouter()` method.
 *
 * This test class tests that the router correctly marshals the allowed methods
 * for a match that matches the path, but not the request method.
 */
abstract class AbstractImplicitMethodsIntegrationTest extends TestCase
{
    abstract public function getRouter(): RouterInterface;

    public function getImplicitOptionsMiddleware(?ResponseInterface $response = null): ImplicitOptionsMiddleware
    {
        return new ImplicitOptionsMiddleware(
            function () use ($response): ResponseInterface {
                return $response ?? new Response();
            }
        );
    }

    public function getImplicitHeadMiddleware(RouterInterface $router): ImplicitHeadMiddleware
    {
        return new ImplicitHeadMiddleware(
            $router,
            function () {
                return new Stream('php://temp', 'rw');
            }
        );
    }

    /**
     * @return callable(): never
     */
    public function createInvalidResponseFactory(): callable
    {
        return static function (): ResponseInterface {
            self::fail('Response generated when it should not have been');
        };
    }

    /**
     * @psalm-return Generator<non-empty-string,array{0:RequestMethod::*,1:non-empty-list<RequestMethod::*>}>
     */
    public function method(): Generator
    {
        yield 'HEAD: head, post' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_HEAD, RequestMethod::METHOD_POST],
        ];

        yield 'HEAD: head, get' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_HEAD, RequestMethod::METHOD_GET],
        ];

        yield 'HEAD: post, head' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_POST, RequestMethod::METHOD_HEAD],
        ];

        yield 'HEAD: get, head' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_GET, RequestMethod::METHOD_HEAD],
        ];

        yield 'OPTIONS: options, post' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_OPTIONS, RequestMethod::METHOD_POST],
        ];

        yield 'OPTIONS: options, get' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_OPTIONS, RequestMethod::METHOD_GET],
        ];

        yield 'OPTIONS: post, options' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_POST, RequestMethod::METHOD_OPTIONS],
        ];

        yield 'OPTIONS: get, options' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_GET, RequestMethod::METHOD_OPTIONS],
        ];
    }

    /**
     * @psalm-param RequestMethod::* $method
     * @psalm-param non-empty-list<RequestMethod::*> $routes
     * @dataProvider method
     */
    public function testExplicitRequest(string $method, array $routes): void
    {
        $implicitRoute = null;
        $router        = $this->getRouter();
        foreach ($routes as $routeMethod) {
            $route = new Route(
                '/api/v1/me',
                $this->createMock(MiddlewareInterface::class),
                [$routeMethod]
            );
            $router->addRoute($route);

            if ($routeMethod === $method) {
                $implicitRoute = $route;
            }
        }

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe(
            $method === RequestMethod::METHOD_HEAD
                ? $this->getImplicitHeadMiddleware($router)
                : $this->getImplicitOptionsMiddleware(),
        );
        $pipeline->pipe(new MethodNotAllowedMiddleware($this->createInvalidResponseFactory()));

        $finalResponse = (new Response())->withHeader('foo-bar', 'baz');
        $finalResponse->getBody()->write('FOO BAR BODY');

        $finalHandler = $this->createMock(RequestHandlerInterface::class);
        $finalHandler
            ->expects(self::once())
            ->method('handle')
            ->with(self::callback(
                static function (ServerRequestInterface $request) use ($method, $implicitRoute): bool {
                    Assert::assertSame($method, $request->getMethod());
                    Assert::assertNull($request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE));

                    $routeResult = $request->getAttribute(RouteResult::class);
                    assert($routeResult instanceof RouteResult);
                    Assert::assertTrue($routeResult->isSuccess());

                    $matchedRoute = $routeResult->getMatchedRoute();
                    Assert::assertInstanceOf(Route::class, $matchedRoute);
                    Assert::assertSame($implicitRoute, $matchedRoute);

                    return true;
                },
            ))
            ->willReturn($finalResponse);

        $request = new ServerRequest(['REQUEST_METHOD' => $method], [], '/api/v1/me', $method);

        $response = $pipeline->process($request, $finalHandler);

        self::assertSame(StatusCode::STATUS_OK, $response->getStatusCode());
        self::assertSame('FOO BAR BODY', (string) $response->getBody());
        self::assertTrue($response->hasHeader('foo-bar'));
        self::assertSame('baz', $response->getHeaderLine('foo-bar'));
    }

    /**
     * @return Generator<non-empty-string,array{0:RequestMethod::*,1:non-empty-list<RequestMethod::*>}>
     */
    public function withoutImplicitMiddleware(): Generator
    {
        // request method, array of allowed methods for a route.
        yield 'HEAD: get'          => [RequestMethod::METHOD_HEAD, [RequestMethod::METHOD_GET]];
        yield 'HEAD: post'         => [RequestMethod::METHOD_HEAD, [RequestMethod::METHOD_POST]];
        yield 'HEAD: get, post'    => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST],
        ];

        yield 'OPTIONS: get'       => [RequestMethod::METHOD_OPTIONS, [RequestMethod::METHOD_GET]];
        yield 'OPTIONS: post'      => [RequestMethod::METHOD_OPTIONS, [RequestMethod::METHOD_POST]];
        yield 'OPTIONS: get, post' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST],
        ];
    }

    /**
     * In case we are not using Implicit*Middlewares and we don't have any route with explicit method
     * returned response should be 405: Method Not Allowed - handled by MethodNotAllowedMiddleware.
     *
     * @psalm-param RequestMethod::* $requestMethod
     * @psalm-param non-empty-list<RequestMethod::*> $allowedMethods
     * @dataProvider withoutImplicitMiddleware
     */
    public function testWithoutImplicitMiddleware(string $requestMethod, array $allowedMethods): void
    {
        $router = $this->getRouter();
        foreach ($allowedMethods as $routeMethod) {
            $route = new Route(
                '/api/v1/me',
                $this->createMock(MiddlewareInterface::class),
                [$routeMethod]
            );
            $router->addRoute($route);
        }

        $finalResponse = $this->createMock(ResponseInterface::class);
        $finalResponse
            ->method('withStatus')
            ->with(StatusCode::STATUS_METHOD_NOT_ALLOWED)
            ->willReturnSelf();

        $finalResponse
            ->method('withHeader')
            ->with('Allow', implode(',', $allowedMethods))
            ->willReturnSelf();

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe(new MethodNotAllowedMiddleware(static function () use ($finalResponse): ResponseInterface {
            return $finalResponse;
        }));

        $finalHandler = $this->createMock(RequestHandlerInterface::class);
        $finalHandler
            ->expects(self::never())
            ->method('handle');

        $request = new ServerRequest(['REQUEST_METHOD' => $requestMethod], [], '/api/v1/me', $requestMethod);

        $response = $pipeline->process($request, $finalHandler);

        self::assertSame($finalResponse, $response);
    }

    /**
     * Provider for the testImplicitHeadRequest method.
     *
     * Implementations must provide this method. Each test case returned
     * must consist of the following three elements, in order:
     *
     * - string route path (the match string)
     * - array route options (if any/required)
     * - string request path (the path in the ServerRequest instance)
     * - array params (expected route par ameters matched)
     *
     * @psalm-return Generator<array-key,array{
     *     0: non-empty-string,
     *     1: array<string,mixed>,
     *     2: string,
     *     3: array<string,mixed>
     * }>
     */
    abstract public function implicitRoutesAndRequests(): Generator;

    /**
     * @param non-empty-string $routePath
     * @psalm-param array<string,mixed> $routeOptions
     * @psalm-param array<string,mixed> $expectedParams
     * @dataProvider implicitRoutesAndRequests
     */
    public function testImplicitHeadRequest(
        string $routePath,
        array $routeOptions,
        string $requestPath,
        array $expectedParams
    ): void {
        $finalResponse = (new Response())->withHeader('foo-bar', 'baz');
        $finalResponse->getBody()->write('FOO BAR BODY');

        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware2
            ->expects(self::never())
            ->method('process');

        $route1 = new Route($routePath, $middleware1, [RequestMethod::METHOD_GET]);
        $route1->setOptions($routeOptions);
        $middleware1
            ->method('process')
            ->with(
                self::callback(
                    static function (ServerRequestInterface $request) use ($route1, $expectedParams): bool {
                        Assert::assertSame(RequestMethod::METHOD_GET, $request->getMethod());
                        Assert::assertSame(
                            RequestMethod::METHOD_HEAD,
                            $request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE),
                        );

                        $routeResult = $request->getAttribute(RouteResult::class);
                        Assert::assertInstanceOf(RouteResult::class, $routeResult);
                        Assert::assertTrue($routeResult->isSuccess());

                    // Some implementations include more in the matched params than what we expect;
                    // e.g., laminas-router will include the middleware as well.
                        $matchedParams = $routeResult->getMatchedParams();
                        /** @var mixed $value */
                        foreach ($expectedParams as $key => $value) {
                            Assert::assertArrayHasKey($key, $matchedParams);
                            Assert::assertSame($value, $matchedParams[$key]);
                        }

                        $matchedRoute = $routeResult->getMatchedRoute();
                        Assert::assertInstanceOf(Route::class, $matchedRoute);
                        Assert::assertSame($route1, $matchedRoute);

                        return true;
                    },
                ),
            )
            ->willReturn($finalResponse);

        $route2 = new Route($routePath, $middleware2, [RequestMethod::METHOD_POST]);
        $route2->setOptions($routeOptions);

        $router = $this->getRouter();
        $router->addRoute($route1);
        $router->addRoute($route2);

        $finalHandler = $this->createMock(RequestHandlerInterface::class);
        $finalHandler
            ->expects(self::never())
            ->method('handle');

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe($this->getImplicitHeadMiddleware($router));
        $pipeline->pipe(new MethodNotAllowedMiddleware($this->createInvalidResponseFactory()));
        $pipeline->pipe(new DispatchMiddleware());

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_HEAD],
            [],
            $requestPath,
            RequestMethod::METHOD_HEAD
        );

        $response = $pipeline->process($request, $finalHandler);

        self::assertSame(StatusCode::STATUS_OK, $response->getStatusCode());
        self::assertEmpty((string) $response->getBody());
        self::assertTrue($response->hasHeader('foo-bar'));
        self::assertSame('baz', $response->getHeaderLine('foo-bar'));
    }

    /**
     * @param non-empty-string $routePath
     * @psalm-param array<string,mixed> $routeOptions
     * @dataProvider implicitRoutesAndRequests
     */
    public function testImplicitOptionsRequest(
        string $routePath,
        array $routeOptions,
        string $requestPath
    ): void {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $route1      = new Route($routePath, $middleware1, [RequestMethod::METHOD_GET]);
        $route1->setOptions($routeOptions);
        $route2 = new Route($routePath, $middleware2, [RequestMethod::METHOD_POST]);
        $route2->setOptions($routeOptions);

        $router = $this->getRouter();
        $router->addRoute($route1);
        $router->addRoute($route2);

        $finalResponse = $this->createMock(ResponseInterface::class);
        $finalResponse
            ->method('withHeader')
            ->with('Allow', 'GET,POST')
            ->willReturnSelf();
        $finalResponse
            ->method('withStatus')
            ->willReturnSelf();

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe($this->getImplicitOptionsMiddleware($finalResponse));
        $pipeline->pipe(new MethodNotAllowedMiddleware($this->createInvalidResponseFactory()));

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_OPTIONS],
            [],
            $requestPath,
            RequestMethod::METHOD_OPTIONS
        );

        $finalHandler = $this->createMock(RequestHandlerInterface::class);
        $finalHandler
            ->expects(self::never())
            ->method('handle');

        $response = $pipeline->process($request, $finalHandler);

        self::assertSame($finalResponse, $response);
    }

    public function testImplicitOptionsRequestRouteNotFound(): void
    {
        $router = $this->getRouter();

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe($this->getImplicitOptionsMiddleware());
        $pipeline->pipe(new MethodNotAllowedMiddleware($this->createInvalidResponseFactory()));
        $pipeline->pipe(new DispatchMiddleware());

        $finalResponse = (new Response())
            ->withStatus(StatusCode::STATUS_IM_A_TEAPOT)
            ->withHeader('foo-bar', 'baz');
        $finalResponse->getBody()->write('FOO BAR BODY');

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_OPTIONS],
            [],
            '/not-found',
            RequestMethod::METHOD_OPTIONS
        );

        $finalHandler = $this->createMock(RequestHandlerInterface::class);
        $finalHandler
            ->expects(self::once())
            ->method('handle')
            ->with(self::callback(static function (ServerRequestInterface $request): bool {
                Assert::assertSame(RequestMethod::METHOD_OPTIONS, $request->getMethod());

                $routeResult = $request->getAttribute(RouteResult::class);
                assert($routeResult instanceof RouteResult);
                Assert::assertTrue($routeResult->isFailure());
                Assert::assertFalse($routeResult->isSuccess());
                Assert::assertFalse($routeResult->isMethodFailure());
                Assert::assertFalse($routeResult->getMatchedRoute());

                return true;
            }))
            ->willReturn($finalResponse);

        $response = $pipeline->process($request, $finalHandler);

        self::assertSame(StatusCode::STATUS_IM_A_TEAPOT, $response->getStatusCode());
        self::assertSame('FOO BAR BODY', (string) $response->getBody());
        self::assertTrue($response->hasHeader('foo-bar'));
        self::assertSame('baz', $response->getHeaderLine('foo-bar'));
    }
}
