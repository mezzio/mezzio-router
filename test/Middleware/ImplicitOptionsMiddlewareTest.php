<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function implode;

class ImplicitOptionsMiddlewareTest extends TestCase
{
    /** @var ImplicitOptionsMiddleware */
    private $middleware;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    public function setUp()
    {
        $this->response = $this->prophesize(ResponseInterface::class);
        $responseFactory = function () {
            return $this->response->reveal();
        };

        $this->middleware = new ImplicitOptionsMiddleware($responseFactory);
    }

    public function testNonOptionsRequestInvokesHandler()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_GET);
        $request->getAttribute(RouteResult::class, false)->shouldNotBeCalled();

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($response);

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
        $handler->handle($request->reveal())->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($response, $result);
    }

    public function testReturnsResultOfHandlerWhenRouteSupportsOptionsExplicitly()
    {
        $route = $this->prophesize(Route::class);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($response, $result);
    }

    public function testInjectsAllowHeaderInResponseProvidedToConstructorDuringOptionsRequest()
    {
        $allowedMethods = [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST];

        $result = $this->prophesize(RouteResult::class);
        $result->getAllowedMethods()->willReturn($allowedMethods);
        $result->getMatchedRoute()->willReturn(false);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->shouldNotBeCalled();

        $this->response
            ->withHeader('Allow', implode(',', $allowedMethods))
            ->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($this->response->reveal(), $result);
    }
}
