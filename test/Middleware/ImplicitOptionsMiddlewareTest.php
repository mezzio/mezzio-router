<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webimpress\HttpMiddlewareCompatibility\HandlerInterface as RequestHandlerInterface;

use const Webimpress\HttpMiddlewareCompatibility\HANDLER_METHOD;

class ImplicitOptionsMiddlewareTest extends TestCase
{
    /** @var ImplicitOptionsMiddleware */
    private $middleware;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    public function setUp()
    {
        $this->response = $this->prophesize(ResponseInterface::class);
        $this->middleware = new ImplicitOptionsMiddleware($this->response->reveal());
    }

    public function testNonOptionsRequestInvokesHandler()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_GET);
        $request->getAttribute(RouteResult::class, false)->shouldNotBeCalled();

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->{HANDLER_METHOD}($request->reveal())->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($response, $result);
    }

    public function testMissingRouteResultInvokesHandler()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class)->willReturn(null);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->{HANDLER_METHOD}($request->reveal())->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($response, $result);
    }

    public function testReturnsHandlerResultIfNoRoutePresentInRouteResult()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->willReturn(null);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->{HANDLER_METHOD}(Argument::that([$request, 'reveal']))->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response, $result);
    }

    public function testReturnsHandlerResultIfRoutePresentInRouteResultSupportsExplicitOptions()
    {
        $route = $this->prophesize(Route::class);
        $route->implicitOptions()->willReturn(false);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->{HANDLER_METHOD}(Argument::that([$request, 'reveal']))->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response, $result);
    }

    public function testInjectsAllowHeaderInResponseProvidedToConstructorDuringOptionsRequest()
    {
        $allowedMethods = [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST];

        $route = $this->prophesize(Route::class);
        $route->implicitOptions()->willReturn(true);
        $route->getAllowedMethods()->willReturn($allowedMethods);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->{HANDLER_METHOD}($request->reveal())->shouldNotBeCalled();

        $this->response
            ->withHeader('Allow', implode(',', $allowedMethods))
            ->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($this->response->reveal(), $result);
    }
}
