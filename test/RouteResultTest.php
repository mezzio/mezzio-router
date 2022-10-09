<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

/**
 * @see MockObject
 *
 * @covers \Mezzio\Router\RouteResult
 */
final class RouteResultTest extends TestCase
{
    public function testRouteNameIsNotRetrievable(): void
    {
        $result = RouteResult::fromRouteFailure([]);

        self::assertFalse($result->getMatchedRouteName());
    }

    public function testRouteFailureRetrieveAllHttpMethods(): void
    {
        $result = RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);

        self::assertSame(Route::HTTP_METHOD_ANY, $result->getAllowedMethods());
    }

    public function testRouteFailureRetrieveHttpMethods(): void
    {
        $result = RouteResult::fromRouteFailure([]);

        self::assertSame([], $result->getAllowedMethods());
    }

    public function testRouteMatchedParams(): void
    {
        $params = ['foo' => 'bar'];
        $route  = $this->createMock(Route::class);
        $result = RouteResult::fromRoute($route, $params);

        self::assertSame($params, $result->getMatchedParams());
    }

    public function testRouteMethodFailure(): void
    {
        $result = RouteResult::fromRouteFailure(['GET']);

        self::assertTrue($result->isMethodFailure());
    }

    public function testRouteSuccessMethodFailure(): void
    {
        $params = ['foo' => 'bar'];
        $route  = $this->createMock(Route::class);
        $result = RouteResult::fromRoute($route, $params);

        self::assertFalse($result->isMethodFailure());
    }

    /**
     * @psalm-return array{route: Route&MockObject, result: RouteResult}
     */
    public function testFromRouteShouldComposeRouteInResult(): array
    {
        $route = $this->createMock(Route::class);

        $result = RouteResult::fromRoute($route, ['foo' => 'bar']);

        self::assertTrue($result->isSuccess());
        self::assertSame($route, $result->getMatchedRoute());

        return ['route' => $route, 'result' => $result];
    }

    /**
     * @psalm-param array{result:RouteResult,route:Route&MockObject} $data
     * @depends testFromRouteShouldComposeRouteInResult
     */
    public function testAllAccessorsShouldReturnExpectedDataWhenResultCreatedViaFromRoute(array $data): void
    {
        $result = $data['result'];
        $route  = $data['route'];
        assert($route instanceof MockObject);

        $route
            ->expects(self::once())
            ->method('getName')
            ->willReturn('route');

        $route
            ->expects(self::once())
            ->method('getAllowedMethods')
            ->willReturn(['HEAD', 'OPTIONS', 'GET']);

        self::assertSame('route', $result->getMatchedRouteName());
        self::assertSame(['HEAD', 'OPTIONS', 'GET'], $result->getAllowedMethods());
    }

    public function testRouteFailureWithNoAllowedHttpMethodsShouldReportTrueForIsMethodFailure(): void
    {
        $result = RouteResult::fromRouteFailure([]);

        self::assertTrue($result->isMethodFailure());
    }

    public function testFailureResultDoesNotIndicateAMethodFailureIfAllMethodsAreAllowed(): RouteResult
    {
        $result = RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);

        self::assertTrue($result->isFailure());
        self::assertFalse($result->isMethodFailure());

        return $result;
    }

    /**
     * @depends testFailureResultDoesNotIndicateAMethodFailureIfAllMethodsAreAllowed
     */
    public function testAllowedMethodsIncludesASingleWildcardEntryWhenAllMethodsAllowedForFailureResult(
        RouteResult $result
    ): void {
        self::assertSame(Route::HTTP_METHOD_ANY, $result->getAllowedMethods());
    }

    public function testFailureResultProcessedAsMiddlewareDelegatesToHandler(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = RouteResult::fromRouteFailure([]);

        self::assertSame($response, $result->process($request, $handler));
    }

    public function testSuccessfulResultProcessedAsMiddlewareDelegatesToRoute(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::never())
            ->method('handle');

        $route = $this->createMock(Route::class);
        $route
            ->expects(self::once())
            ->method('process')
            ->with($request, $handler)
            ->willReturn($response);

        $result = RouteResult::fromRoute($route);

        self::assertSame($response, $result->process($request, $handler));
    }
}
