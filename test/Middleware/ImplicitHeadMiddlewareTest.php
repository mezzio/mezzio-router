<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ImplicitHeadMiddlewareTest extends TestCase
{
    /** @var ImplicitHeadMiddleware */
    private $middleware;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    /** @var RouterInterface|ObjectProphecy */
    private $router;

    /** @var StreamInterface|ObjectProphecy */
    private $stream;

    public function setUp()
    {
        $this->router = $this->prophesize(RouterInterface::class);
        $this->stream = $this->prophesize(StreamInterface::class);

        $streamFactory = function () {
            return $this->stream->reveal();
        };

        $this->middleware = new ImplicitHeadMiddleware($this->router->reveal(), $streamFactory);
        $this->response = $this->prophesize(ResponseInterface::class);
    }

    public function testReturnsResultOfHandlerOnNonHeadRequests()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_GET);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testReturnsResultOfHandlerWhenNoRouteResultPresentInRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn(null);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testReturnsResultOfHandlerWhenRouteSupportsHeadExplicitly()
    {
        $route = $this->prophesize(Route::class);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->willReturn([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testReturnsResultOfHandlerWhenRouteDoesNotExplicitlySupportHeadAndDoesNotSupportGet()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->willReturn(false);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);

        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(true);

        $this->router->match($request)->will([$result, 'reveal']);
        $request->withAttribute(RouteResult::class, $result)->will([$request, 'reveal']);
        $request->withAttribute(\Zend\Expressive\Router\RouteResult::class, $result)->will([$request, 'reveal']);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testInvokesHandlerWhenRouteImplicitlySupportsHeadAndSupportsGet()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->willReturn(false);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);
        $request
            ->withAttribute(
                ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                RequestMethod::METHOD_HEAD
            )
            ->will([$request, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class);
        $response->withBody($this->stream->reveal())->will([$response, 'reveal']);

        $route = $this->prophesize(Route::class);

        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request->withAttribute(RouteResult::class, $result->reveal())->will([$request, 'reveal']);
        $request->withAttribute(\Zend\Expressive\Router\RouteResult::class, $result->reveal())->will([$request, 'reveal']);

        $this->router->match($request)->will([$result, 'reveal']);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::that([$request, 'reveal']))
            ->will([$response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response->reveal(), $result);
    }
}