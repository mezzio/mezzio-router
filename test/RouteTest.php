<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Mezzio\Router\Exception\InvalidArgumentException;
use Mezzio\Router\Route;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Mezzio\Router\Route */
final class RouteTest extends TestCase
{
    /** @var MiddlewareInterface&MockObject */
    private MiddlewareInterface $noopMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->noopMiddleware = $this->createMock(MiddlewareInterface::class);
    }

    public function testRoutePathIsRetrievable(): void
    {
        $route = new Route('/foo', $this->noopMiddleware);

        self::assertSame('/foo', $route->getPath());
    }

    public function testRouteMiddlewareIsRetrievable(): void
    {
        $route = new Route('/foo', $this->noopMiddleware);

        self::assertSame($this->noopMiddleware, $route->getMiddleware());
    }

    public function testRouteInstanceAcceptsAllHttpMethodsByDefault(): void
    {
        $route = new Route('/foo', $this->noopMiddleware);

        self::assertSame(Route::HTTP_METHOD_ANY, $route->getAllowedMethods());
    }

    public function testRouteAllowsSpecifyingHttpMethods(): void
    {
        $methods = [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST];
        $route   = new Route('/foo', $this->noopMiddleware, $methods);

        self::assertSame($methods, $route->getAllowedMethods());
    }

    public function testRouteCanMatchMethod(): void
    {
        $methods = [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST];
        $route   = new Route('/foo', $this->noopMiddleware, $methods);

        self::assertTrue($route->allowsMethod(RequestMethod::METHOD_GET));
        self::assertTrue($route->allowsMethod(RequestMethod::METHOD_POST));
        self::assertFalse($route->allowsMethod(RequestMethod::METHOD_PATCH));
        self::assertFalse($route->allowsMethod(RequestMethod::METHOD_DELETE));
    }

    public function testRouteHeadMethodIsNotAllowedByDefault(): void
    {
        $route = new Route('/foo', $this->noopMiddleware, [RequestMethod::METHOD_GET]);

        self::assertFalse($route->allowsMethod(RequestMethod::METHOD_HEAD));
    }

    public function testRouteOptionsMethodIsNotAllowedByDefault(): void
    {
        $route = new Route('/foo', $this->noopMiddleware, [RequestMethod::METHOD_GET]);

        self::assertFalse($route->allowsMethod(RequestMethod::METHOD_OPTIONS));
    }

    public function testRouteAllowsSpecifyingOptions(): void
    {
        $options = ['foo' => 'bar'];
        $route   = new Route('/foo', $this->noopMiddleware);
        $route->setOptions($options);

        self::assertSame($options, $route->getOptions());
    }

    public function testRouteOptionsAreEmptyByDefault(): void
    {
        $route = new Route('/foo', $this->noopMiddleware);

        self::assertSame([], $route->getOptions());
    }

    public function testRouteNameForRouteAcceptingAnyMethodMatchesPathByDefault(): void
    {
        $route = new Route('/test', $this->noopMiddleware);

        self::assertSame('/test', $route->getName());
    }

    public function testRouteNameWithConstructor(): void
    {
        $route = new Route('/test', $this->noopMiddleware, [RequestMethod::METHOD_GET], 'test');

        self::assertSame('test', $route->getName());
    }

    public function testRouteNameWithGET(): void
    {
        $route = new Route('/test', $this->noopMiddleware, [RequestMethod::METHOD_GET]);

        self::assertSame('/test^GET', $route->getName());
    }

    public function testRouteNameWithGetAndPost(): void
    {
        $route = new Route('/test', $this->noopMiddleware, [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST]);

        self::assertSame('/test^GET' . Route::HTTP_METHOD_SEPARATOR . RequestMethod::METHOD_POST, $route->getName());
    }

    public function testRouteNameIsMutable(): void
    {
        $route = new Route('/foo', $this->noopMiddleware, [RequestMethod::METHOD_GET], 'foo');
        $route->setName('bar');

        self::assertSame('bar', $route->getName());
    }

    /**
     * @return array<array-key, array{0: list<mixed>}>
     */
    public function invalidHttpMethodsProvider(): array
    {
        return [
            [[123]],
            [[123, 456]],
            [['@@@']],
            [['@@@', '@@@']],
        ];
    }

    /**
     * @param list<mixed> $invalidHttpMethods
     * @dataProvider invalidHttpMethodsProvider
     */
    public function testThrowsExceptionIfInvalidHttpMethodsAreProvided(array $invalidHttpMethods): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('One or more HTTP methods were invalid');

        /** @psalm-suppress MixedArgumentTypeCoercion */
        new Route('/test', $this->noopMiddleware, $invalidHttpMethods);
    }

    public function testAllowsHttpInteropMiddleware(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $route      = new Route('/test', $middleware, Route::HTTP_METHOD_ANY);

        self::assertSame($middleware, $route->getMiddleware());
    }

    public function testRouteIsMiddlewareAndProxiesToComposedMiddleware(): void
    {
        $request    = $this->createMock(ServerRequestInterface::class);
        $handler    = $this->createMock(RequestHandlerInterface::class);
        $response   = $this->createMock(ResponseInterface::class);
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware
            ->method('process')
            ->with($request, $handler)
            ->willReturn($response);

        $route = new Route('/foo', $middleware);

        self::assertSame($response, $route->process($request, $handler));
    }

    public function testConstructorShouldRaiseExceptionIfMethodsArgumentIsAnEmptyArray(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty');

        new Route('/foo', $middleware, []);
    }
}
