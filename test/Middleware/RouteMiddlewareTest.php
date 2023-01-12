<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Mezzio\Router\Middleware\RouteMiddleware */
final class RouteMiddlewareTest extends TestCase
{
    /** @var RouterInterface&MockObject */
    private RouterInterface $router;

    /** @var ResponseInterface&MockObject */
    private ResponseInterface $response;

    /** @var ServerRequestInterface&MockObject */
    private ServerRequestInterface $request;

    /** @var RequestHandlerInterface&MockObject */
    private RequestHandlerInterface $handler;

    private RouteMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router   = $this->createMock(RouterInterface::class);
        $this->request  = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->handler  = $this->createMock(RequestHandlerInterface::class);

        $this->middleware = new RouteMiddleware($this->router);
    }

    public function testRoutingFailureDueToHttpMethodCallsHandlerWithRequestComposingRouteResult(): void
    {
        $result = RouteResult::fromRouteFailure(['GET', 'POST']);

        $this->router
            ->method('match')
            ->with($this->request)
            ->willReturn($result);

        $this->handler
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $this->request
            ->expects(self::once())
            ->method('withAttribute')
            ->withConsecutive(
                [
                    RouteResult::class,
                    $result,
                ]
            )->willReturnSelf();

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $response);
    }

    public function testGeneralRoutingFailureInvokesHandlerWithRequestComposingRouteResult(): void
    {
        $result = RouteResult::fromRouteFailure(null);

        $this->router
            ->method('match')
            ->with($this->request)
            ->willReturn($result);

        $this->handler
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $this->request
            ->expects(self::exactly(1))
            ->method('withAttribute')
            ->withConsecutive(
                [
                    RouteResult::class,
                    $result,
                ]
            )->willReturnSelf();

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $response);
    }

    public function testRoutingSuccessInvokesHandlerWithRequestComposingRouteResultAndAttributes(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $result     = RouteResult::fromRoute(
            new Route('/foo', $middleware),
            ['foo' => 'bar', 'baz' => 'bat']
        );

        $this->router
            ->expects(self::once())
            ->method('match')
            ->with($this->request)
            ->willReturn($result);

        $this->request
            ->expects(self::exactly(3))
            ->method('withAttribute')
            ->withConsecutive(
                [
                    RouteResult::class,
                    $result,
                ],
                [
                    'foo',
                    'bar',
                ],
                [
                    'baz',
                    'bat',
                ]
            )->willReturnSelf();

        $this->handler
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $response);
    }
}
