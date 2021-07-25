<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MethodNotAllowedMiddlewareTest extends TestCase
{
    /** @var RequestHandlerInterface&MockObject */
    private $handler;

    /** @var MethodNotAllowedMiddleware */
    private $middleware;

    /** @var ServerRequestInterface&MockObject */
    private $request;

    /** @var ResponseInterface&MockObject */
    private $response;

    protected function setUp(): void
    {
        $this->handler   = $this->createMock(RequestHandlerInterface::class);
        $this->request   = $this->createMock(ServerRequestInterface::class);
        $this->response  = $this->createMock(ResponseInterface::class);
        $responseFactory = function (): ResponseInterface {
            return $this->response;
        };

        $this->middleware = new MethodNotAllowedMiddleware($responseFactory);
    }

    public function testDelegatesToHandlerIfNoRouteResultPresentInRequest(): void
    {
        $this->request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn(null);

        $this->handler
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $this->response
            ->expects(self::never())
            ->method('withStatus');

        $this->response
            ->expects(self::never())
            ->method('withHeader');

        $this->assertSame(
            $this->response,
            $this->middleware->process($this->request, $this->handler)
        );
    }

    public function testDelegatesToHandlerIfRouteResultNotAMethodFailure(): void
    {
        $result = RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);

        $this->request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn($result);

        $this->handler
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $this->response
            ->expects(self::never())
            ->method('withStatus');

        $this->response
            ->expects(self::never())
            ->method('withHeader');

        $this->assertSame(
            $this->response,
            $this->middleware->process($this->request, $this->handler)
        );
    }

    public function testReturns405ResponseWithAllowHeaderIfResultDueToMethodFailure(): void
    {
        $result = RouteResult::fromRouteFailure(['GET', 'POST']);

        $this->request
            ->method('getAttribute')
            ->with(RouteResult::class)
            ->willReturn($result);

        $this->handler
            ->expects(self::never())
            ->method('handle');

        $this->response
            ->expects(self::once())
            ->method('withStatus')
            ->with(StatusCode::STATUS_METHOD_NOT_ALLOWED)
            ->willReturnSelf();

        $this->response
            ->expects(self::once())
            ->method('withHeader')
            ->with('Allow', 'GET,POST')
            ->willReturnSelf();

        $this->assertSame(
            $this->response,
            $this->middleware->process($this->request, $this->handler)
        );
    }
}
