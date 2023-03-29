<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\Test\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Mezzio\Router\Middleware\ImplicitOptionsMiddleware */
final class ImplicitOptionsMiddlewareTest extends TestCase
{
    private ResponseInterface $response;
    private ImplicitOptionsMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->response   = new Response();
        $this->middleware = new ImplicitOptionsMiddleware(new ResponseFactory($this->response));
    }

    public function testNonOptionsRequestInvokesHandler(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_GET);

        $request
            ->expects(self::never())
            ->method('getAttribute');

        $response = $this->createMock(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        self::assertSame($response, $result);
    }

    public function testMissingRouteResultInvokesHandler(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_OPTIONS);

        $request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn(null);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        self::assertSame($response, $result);
    }

    public function testReturnsResultOfHandlerWhenRouteSupportsOptionsExplicitly(): void
    {
        $route = $this->createMock(Route::class);

        $result = RouteResult::fromRoute($route);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_OPTIONS);

        $request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn($result);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        self::assertSame($response, $result);
    }

    public function testInjectsAllowHeaderInResponseProvidedToConstructorDuringOptionsRequest(): void
    {
        $allowedMethods = [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST];

        $result = RouteResult::fromRouteFailure($allowedMethods);

        $request = (new ServerRequest())
            ->withMethod(RequestMethod::METHOD_OPTIONS)
            ->withAttribute(RouteResult::class, $result);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::never())
            ->method('handle');

        self::assertFalse($this->response->hasHeader('Allow'));

        $response = $this->middleware->process($request, $handler);

        self::assertNotSame($this->response, $response);
        self::assertTrue($response->hasHeader('Allow'));
    }

    public function testReturnsResultOfHandlerWhenRouteNotFound(): void
    {
        $result = RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_OPTIONS);

        $request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn($result);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        self::assertSame($response, $result);
    }
}
