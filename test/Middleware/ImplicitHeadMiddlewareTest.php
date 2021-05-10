<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Router\RouteResult as ZendExpressiveRouteResult;

class ImplicitHeadMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /** @var ImplicitHeadMiddleware */
    private $middleware;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    /** @var RouterInterface|ObjectProphecy */
    private $router;

    /** @var StreamInterface|ObjectProphecy */
    private $stream;

    protected function setUp(): void
    {
        $this->router = $this->prophesize(RouterInterface::class);
        $this->stream = $this->prophesize(StreamInterface::class);

        $streamFactory = function () {
            return $this->stream->reveal();
        };

        $this->middleware = new ImplicitHeadMiddleware($this->router->reveal(), $streamFactory);
        $this->response   = $this->prophesize(ResponseInterface::class);
    }

    public function testReturnsResultOfHandlerOnNonHeadRequests(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_GET);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testReturnsResultOfHandlerWhenNoRouteResultPresentInRequest(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn(null);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testReturnsResultOfHandlerWhenRouteSupportsHeadExplicitly(): void
    {
        $route  = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn($result);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testReturnsResultOfHandlerWhenRouteDoesNotExplicitlySupportHeadAndDoesNotSupportGet(): void
    {
        $result = RouteResult::fromRouteFailure([]);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn($result);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);

        $this->router->match($request)->willReturn($result);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $response = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $response);
    }

    public function testInvokesHandlerWhenRouteImplicitlySupportsHeadAndSupportsGet(): void
    {
        $result = RouteResult::fromRouteFailure([]);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn($result);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);
        $request
            ->withAttribute(
                ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                RequestMethod::METHOD_HEAD
            )
            ->will([$request, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class);
        $response->withBody($this->stream->reveal())->will([$response, 'reveal']);

        $route  = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal());

        $request->withAttribute(RouteResult::class, $result)->will([$request, 'reveal']);
        $request->withAttribute(ZendExpressiveRouteResult::class, $result)->will([$request, 'reveal']);

        $this->router->match($request)->willReturn($result);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::that([$request, 'reveal']))
            ->will([$response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response->reveal(), $result);
    }

    public function testInvokesHandlerWithRequestComposingRouteResultAndAttributes(): void
    {
        $result = RouteResult::fromRouteFailure([]);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn($result);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);
        $request
            ->withAttribute(
                ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                RequestMethod::METHOD_HEAD
            )
            ->will([$request, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class);
        $response->withBody($this->stream->reveal())->will([$response, 'reveal']);

        $route  = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal(), ['foo' => 'bar', 'baz' => 'bat']);

        $request->withAttribute(RouteResult::class, $result)->will([$request, 'reveal']);
        $request->withAttribute(ZendExpressiveRouteResult::class, $result)->will([$request, 'reveal']);
        $request->withAttribute('foo', 'bar')->will([$request, 'reveal'])->shouldBeCalled();
        $request->withAttribute('baz', 'bat')->will([$request, 'reveal'])->shouldBeCalled();

        $this->router->match($request)->willReturn($result);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::that([$request, 'reveal']))
            ->will([$response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response->reveal(), $result);
    }
}
