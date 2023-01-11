<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Mezzio\Router\Middleware\ImplicitHeadMiddleware */
final class ImplicitHeadMiddlewareTest extends TestCase
{
    /** @var ResponseInterface&MockObject */
    private ResponseInterface $response;

    /** @var RouterInterface&MockObject */
    private RouterInterface $router;

    /** @var StreamInterface&MockObject */
    private StreamInterface $stream;

    private ImplicitHeadMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->createMock(RouterInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);

        $streamFactory = fn (): StreamInterface => $this->stream;

        $this->middleware = new ImplicitHeadMiddleware($this->router, $streamFactory);
        $this->response   = $this->createMock(ResponseInterface::class);
    }

    public function testReturnsResultOfHandlerOnNonHeadRequests(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_GET);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($this->response);

        $result = $this->middleware->process($request, $handler);

        self::assertSame($this->response, $result);
    }

    public function testReturnsResultOfHandlerWhenNoRouteResultPresentInRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_HEAD);

        $request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn(null);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($this->response);

        $result = $this->middleware->process($request, $handler);

        self::assertSame($this->response, $result);
    }

    public function testReturnsResultOfHandlerWhenRouteSupportsHeadExplicitly(): void
    {
        $route  = $this->createMock(Route::class);
        $result = RouteResult::fromRoute($route);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_HEAD);

        $request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn($result);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($this->response);

        $result = $this->middleware->process($request, $handler);

        self::assertSame($this->response, $result);
    }

    public function testReturnsResultOfHandlerWhenRouteDoesNotExplicitlySupportHeadAndDoesNotSupportGet(): void
    {
        $result = RouteResult::fromRouteFailure([]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_HEAD);

        $request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn($result);

        $request
            ->expects(self::once())
            ->method('withMethod')
            ->with(RequestMethod::METHOD_GET)
            ->willReturnSelf();

        $this->router
            ->expects(self::once())
            ->method('match')
            ->with($request)
            ->willReturn($result);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($this->response);

        $response = $this->middleware->process($request, $handler);

        self::assertSame($this->response, $response);
    }

    public function testInvokesHandlerWhenRouteImplicitlySupportsHeadAndSupportsGet(): void
    {
        $result = RouteResult::fromRouteFailure([]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_HEAD);

        $request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn($result);

        $request
            ->expects(self::exactly(2))
            ->method('withMethod')
            ->with(RequestMethod::METHOD_GET)
            ->willReturnSelf();

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects(self::once())
            ->method('withBody')
            ->with($this->stream)
            ->willReturnSelf();

        $route  = $this->createMock(Route::class);
        $result = RouteResult::fromRoute($route);

        $request
            ->expects(self::exactly(3))
            ->method('withAttribute')
            ->withConsecutive(
                [
                    RouteResult::class,
                    $result,
                ],
                [
                    'Zend\Expressive\Router\RouteResult',
                    $result,
                ],
                [
                    ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                    RequestMethod::METHOD_HEAD,
                ]
            )
            ->willReturnSelf();

        $this->router
            ->expects(self::once())
            ->method('match')
            ->with($request)
            ->willReturn($result);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        self::assertSame($response, $result);
    }

    public function testInvokesHandlerWithRequestComposingRouteResultAndAttributes(): void
    {
        $result = RouteResult::fromRouteFailure([]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn(RequestMethod::METHOD_HEAD);

        $request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn($result);

        $request
            ->expects(self::exactly(2))
            ->method('withMethod')
            ->with(RequestMethod::METHOD_GET)
            ->willReturnSelf();

        $route                     = $this->createMock(Route::class);
        $resultForRequestMethodGet = RouteResult::fromRoute($route, ['foo' => 'bar', 'baz' => 'bat']);

        $request
            ->expects(self::exactly(5))
            ->method('withAttribute')
            ->withConsecutive(
                [
                    'foo',
                    'bar',
                ],
                [
                    'baz',
                    'bat',
                ],
                [
                    RouteResult::class,
                    $resultForRequestMethodGet,
                ],
                [
                    'Zend\Expressive\Router\RouteResult',
                    $resultForRequestMethodGet,
                ],
                [
                    ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                    RequestMethod::METHOD_HEAD,
                ]
            )
            ->willReturnSelf();

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects(self::once())
            ->method('withBody')
            ->with($this->stream)
            ->willReturnSelf();

        $this->router
            ->expects(self::once())
            ->method('match')
            ->with($request)
            ->willReturn($resultForRequestMethodGet);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        self::assertSame($response, $result);
    }
}
