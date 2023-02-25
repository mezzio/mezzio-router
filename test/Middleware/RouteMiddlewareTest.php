<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;

#[CoversClass(RouteMiddleware::class)]
final class RouteMiddlewareTest extends TestCase
{
    private RouterInterface&MockObject $router;
    private RouteMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router     = $this->createMock(RouterInterface::class);
        $this->middleware = new RouteMiddleware($this->router);
    }

    public function testRoutingFailureDueToHttpMethodCallsHandlerWithRequestComposingRouteResult(): void
    {
        $result   = RouteResult::fromRouteFailure(['GET', 'POST']);
        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);
        $request  = new ServerRequest();

        $this->router
            ->method('match')
            ->with($request)
            ->willReturn($result);

        self::assertSame($response, $this->middleware->process($request, $handler));
        self::assertTrue($handler->didExecute());
        $received = $handler->receivedRequest();
        self::assertNotSame($request, $received);
        self::assertSame($result, $received->getAttribute(RouteResult::class));
    }

    public function testGeneralRoutingFailureInvokesHandlerWithRequestComposingRouteResult(): void
    {
        $result = RouteResult::fromRouteFailure(null);

        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);
        $request  = new ServerRequest();

        $this->router
            ->method('match')
            ->with($request)
            ->willReturn($result);

        self::assertSame($response, $this->middleware->process($request, $handler));
        self::assertTrue($handler->didExecute());
        $received = $handler->receivedRequest();
        self::assertNotSame($request, $received);
        self::assertSame($result, $received->getAttribute(RouteResult::class));
    }

    public function testRoutingSuccessInvokesHandlerWithRequestComposingRouteResultAndAttributes(): void
    {
        $result = RouteResult::fromRoute(
            new Route(
                '/foo',
                $this->createMock(MiddlewareInterface::class)
            ),
            ['foo' => 'bar', 'baz' => 'bat'],
        );

        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);
        $request  = new ServerRequest();

        $this->router
            ->expects(self::once())
            ->method('match')
            ->with($request)
            ->willReturn($result);

        self::assertSame(
            $response,
            $this->middleware->process($request, $handler),
        );
        self::assertTrue($handler->didExecute());
        $received = $handler->receivedRequest();
        self::assertNotSame($request, $received);
        self::assertSame('bar', $received->getAttribute('foo'));
        self::assertSame('bat', $received->getAttribute('baz'));
    }
}
