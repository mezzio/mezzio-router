<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Mezzio\Router\Middleware\DispatchMiddleware */
final class DispatchMiddlewareTest extends TestCase
{
    /** @var RequestHandlerInterface&MockObject */
    private RequestHandlerInterface $handler;

    /** @var ServerRequestInterface&MockObject */
    private ServerRequestInterface $request;

    /** @var ResponseInterface&MockObject */
    private ResponseInterface $response;

    private DispatchMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->response = $this->createMock(ResponseInterface::class);
        $this->request  = $this->createMock(ServerRequestInterface::class);
        $this->handler  = $this->createMock(RequestHandlerInterface::class);

        $this->middleware = new DispatchMiddleware();
    }

    public function testInvokesHandlerIfRequestDoesNotContainRouteResult(): void
    {
        $this->request
            ->expects(self::once())
            ->method('getAttribute')
            ->with(RouteResult::class, false)
            ->willReturnArgument(1);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $response);
    }

    public function testInvokesRouteResultWhenPresent(): void
    {
        $this->handler
            ->expects(self::never())
            ->method('handle');

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->expects(self::once())
            ->method('process')
            ->with($this->request, $this->handler)
            ->willReturn($this->response);

        $this->request
            ->expects(self::once())
            ->method('getAttribute')
            ->with(RouteResult::class, false)
            ->willReturn($routeResult);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $response);
    }
}
