<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;

/** @covers \Mezzio\Router\Middleware\ImplicitHeadMiddleware */
final class ImplicitHeadMiddlewareTest extends TestCase
{
    /** @var RouterInterface&MockObject */
    private RouterInterface $router;

    private ImplicitHeadMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router     = $this->createMock(RouterInterface::class);
        $this->middleware = new ImplicitHeadMiddleware(
            $this->router,
            fn (): StreamInterface => $this->createMock(StreamInterface::class),
        );
    }

    /** @return array<non-empty-string, array{0: non-empty-string}> */
    public static function nonHeadMethods(): array
    {
        return [
            RequestMethod::METHOD_GET     => [RequestMethod::METHOD_GET],
            RequestMethod::METHOD_POST    => [RequestMethod::METHOD_POST],
            RequestMethod::METHOD_PATCH   => [RequestMethod::METHOD_PATCH],
            RequestMethod::METHOD_PUT     => [RequestMethod::METHOD_PUT],
            RequestMethod::METHOD_DELETE  => [RequestMethod::METHOD_DELETE],
            RequestMethod::METHOD_OPTIONS => [RequestMethod::METHOD_OPTIONS],
            RequestMethod::METHOD_TRACE   => [RequestMethod::METHOD_TRACE],
            RequestMethod::METHOD_CONNECT => [RequestMethod::METHOD_CONNECT],
        ];
    }

    #[DataProvider('nonHeadMethods')]
    public function testReturnsResultOfHandlerForNonHeadRequests(string $method): void
    {
        $request  = (new ServerRequest())->withMethod($method);
        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $handler);

        self::assertTrue($handler->didExecute());
        self::assertSame($request, $handler->receivedRequest());
        self::assertSame($response, $result);
    }

    public function testReturnsResultOfHandlerWhenNoRouteResultPresentInRequest(): void
    {
        $request  = (new ServerRequest())->withMethod(RequestMethod::METHOD_HEAD);
        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $handler);

        self::assertTrue($handler->didExecute());
        self::assertSame($request, $handler->receivedRequest());
        self::assertSame($response, $result);
    }

    public function testReturnsResultOfHandlerWhenTheRouteResultMatchesARouteThatSupportsHeadRequests(): void
    {
        $route   = new Route('/foo', $this->createMock(MiddlewareInterface::class), [RequestMethod::METHOD_HEAD], 'route-name');
        $request = (new ServerRequest())
            ->withMethod(RequestMethod::METHOD_HEAD)
            ->withAttribute(RouteResult::class, RouteResult::fromRoute($route));

        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $handler);

        self::assertTrue($handler->didExecute());
        self::assertSame($request, $handler->receivedRequest());
        self::assertSame($response, $result);
    }

    public function testReturnsResultOfHandlerWhenRouteSupportsHeadExplicitly(): void
    {
        $route   = $this->createMock(Route::class);
        $result  = RouteResult::fromRoute($route);
        $request = (new ServerRequest())
            ->withMethod(RequestMethod::METHOD_HEAD)
            ->withAttribute(RouteResult::class, $result);

        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $handler);

        self::assertTrue($handler->didExecute());
        self::assertSame($request, $handler->receivedRequest());
        self::assertSame($response, $result);
    }

    public function testReturnsResultOfHandlerWhenRouteDoesNotExplicitlySupportHeadAndDoesNotSupportGet(): void
    {
        $request = (new ServerRequest())
            ->withMethod(RequestMethod::METHOD_HEAD)
            ->withAttribute(RouteResult::class, RouteResult::fromRouteFailure([]));

        $this->router->expects(self::once())
            ->method('match')
            ->with(self::callback(static function (ServerRequestInterface $matchInput) use ($request): bool {
                self::assertNotSame($request, $matchInput);
                self::assertSame(RequestMethod::METHOD_GET, $matchInput->getMethod());

                return true;
            }))->willReturn(RouteResult::fromRouteFailure([]));

        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $handler);

        self::assertTrue($handler->didExecute());
        self::assertSame($request, $handler->receivedRequest());
        self::assertSame($response, $result);
    }

    public function testInvokesHandlerWhenRouteImplicitlySupportsHeadAndSupportsGet(): void
    {
        $matchFailure = RouteResult::fromRouteFailure([]);
        $matchedRoute = new Route('/foo', $this->createMock(MiddlewareInterface::class), [RequestMethod::METHOD_GET], 'route-name');
        $matchSuccess = RouteResult::fromRoute($matchedRoute);

        $request = (new ServerRequest())
            ->withMethod(RequestMethod::METHOD_HEAD)
            ->withAttribute(RouteResult::class, $matchFailure);

        $this->router->expects(self::once())
            ->method('match')
            ->with(self::callback(static function (ServerRequestInterface $matchInput) use ($request): bool {
                self::assertNotSame($request, $matchInput);
                self::assertSame(RequestMethod::METHOD_GET, $matchInput->getMethod());

                return true;
            }))->willReturn($matchSuccess);

        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $handler);

        self::assertTrue($handler->didExecute());
        self::assertNotSame($request, $handler->receivedRequest());
        self::assertNotSame($response, $result);

        self::assertEmpty((string) $result->getBody());

        $received = $handler->receivedRequest();
        self::assertSame($matchSuccess, $received->getAttribute(RouteResult::class));
        self::assertSame(RequestMethod::METHOD_HEAD, $received->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE));
        self::assertSame(RequestMethod::METHOD_GET, $received->getMethod());
    }

    public function testInvokesHandlerWithRequestComposingRouteResultAndAttributes(): void
    {
        $matchFailure = RouteResult::fromRouteFailure([]);
        $matchedRoute = new Route('/foo', $this->createMock(MiddlewareInterface::class), [RequestMethod::METHOD_GET], 'route-name');
        $matchSuccess = RouteResult::fromRoute($matchedRoute, ['foo' => 'bar', 'baz' => 'bat']);

        $request = (new ServerRequest())
            ->withMethod(RequestMethod::METHOD_HEAD)
            ->withAttribute(RouteResult::class, $matchFailure);

        $this->router->expects(self::once())
            ->method('match')
            ->with(self::callback(static function (ServerRequestInterface $matchInput) use ($request): bool {
                self::assertNotSame($request, $matchInput);
                self::assertSame(RequestMethod::METHOD_GET, $matchInput->getMethod());

                return true;
            }))->willReturn($matchSuccess);

        $response = new TextResponse('Whatever');
        $handler  = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $handler);

        self::assertTrue($handler->didExecute());
        self::assertNotSame($request, $handler->receivedRequest());
        self::assertNotSame($response, $result);

        self::assertEmpty((string) $result->getBody());

        $received = $handler->receivedRequest();
        self::assertSame($matchSuccess, $received->getAttribute(RouteResult::class));
        self::assertSame(RequestMethod::METHOD_HEAD, $received->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE));
        self::assertSame(RequestMethod::METHOD_GET, $received->getMethod());
        self::assertSame('bar', $received->getAttribute('foo'));
        self::assertSame('bat', $received->getAttribute('baz'));
    }
}
