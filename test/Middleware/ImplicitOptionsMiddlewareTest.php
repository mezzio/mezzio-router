<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function implode;

/** @covers \Mezzio\Router\Middleware\ImplicitOptionsMiddleware */
final class ImplicitOptionsMiddlewareTest extends TestCase
{
    /** @var ResponseInterface&MockObject */
    private ResponseInterface $response;

    private ImplicitOptionsMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->response  = $this->createMock(ResponseInterface::class);
        $responseFactory = fn (): ResponseInterface => $this->response;

        $this->middleware = new ImplicitOptionsMiddleware($responseFactory);
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

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_OPTIONS);

        $request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn($result);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::never())
            ->method('handle');

        $this->response
            ->method('withStatus')
            ->willReturnSelf();

        $this->response
            ->expects(self::once())
            ->method('withHeader')
            ->with('Allow', implode(',', $allowedMethods))
            ->willReturnSelf();

        $result = $this->middleware->process($request, $handler);

        self::assertSame($this->response, $result);
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
