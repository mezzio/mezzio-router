<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Router;

use Mezzio\Router\DispatchMiddleware;
use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webimpress\HttpMiddlewareCompatibility\HandlerInterface;
use Webimpress\HttpMiddlewareCompatibility\MiddlewareInterface;

use const Webimpress\HttpMiddlewareCompatibility\HANDLER_METHOD;

class DispatchMiddlewareTest extends TestCase
{
    /** @var HandlerInterface|ObjectProphecy */
    private $handler;

    /** @var DispatchMiddleware */
    private $middleware;

    public function setUp()
    {
        $this->request    = $this->prophesize(ServerRequestInterface::class);
        $this->handler    = $this->prophesize(HandlerInterface::class);
        $this->middleware = new DispatchMiddleware();
    }

    public function testInvokesDelegateIfRequestDoesNotContainRouteResult()
    {
        $expected = $this->prophesize(ResponseInterface::class)->reveal();
        $this->request->getAttribute(RouteResult::class, false)->willReturn(false);
        $this->handler->{HANDLER_METHOD}($this->request->reveal())->willReturn($expected);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame($expected, $response);
    }

    public function testInvokesMatchedMiddlewareWhenRouteResult()
    {
        $this->handler->{HANDLER_METHOD}()->shouldNotBeCalled();

        $expected = $this->prophesize(ResponseInterface::class)->reveal();
        $routedMiddleware = $this->prophesize(MiddlewareInterface::class);
        $routedMiddleware
            ->process($this->request->reveal(), $this->handler->reveal())
            ->willReturn($expected);

        $routeResult = RouteResult::fromRoute(new Route('/', $routedMiddleware->reveal()));

        $this->request->getAttribute(RouteResult::class, false)->willReturn($routeResult);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame($expected, $response);
    }

    public function invalidMiddleware()
    {
        return [
            // @codingStandardsIgnoreStart
            // There are more types we could test, but Route has a number of tests
            // in place already, and these are the three it allows that the dispatch
            // middleware cannot allow.
            'string'   => ['middleware'],
            'array'    => [['middleware']],
            'callable' => [function () {}],
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * @dataProvider invalidMiddleware
     * @param mixed $middleware
     */
    public function testInvalidRoutedMiddlewareInRouteResultResultsInException($middleware)
    {
        $this->handler->{HANDLER_METHOD}()->shouldNotBeCalled();
        $routeResult = RouteResult::fromRoute(new Route('/', $middleware));
        $this->request->getAttribute(RouteResult::class, false)->willReturn($routeResult);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expects an http-interop');
        $this->middleware->process($this->request->reveal(), $this->handler->reveal());
    }
}
