<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Mezzio\Router\Response\CallableResponseFactoryDecorator;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function implode;
use function is_array;
use function is_callable;

/**
 * Emit a 405 Method Not Allowed response
 *
 * If the request composes a route result, and the route result represents a
 * failure due to request method, this middleware will emit a 405 response,
 * along with an Allow header indicating allowed methods, as reported by the
 * route result.
 *
 * If no route result is composed, and/or it's not the result of a method
 * failure, it passes handling to the provided handler.
 *
 * @final
 */
class MethodNotAllowedMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /**
     * @param (callable():ResponseInterface)|ResponseFactoryInterface $responseFactory
     */
    public function __construct($responseFactory)
    {
        if (is_callable($responseFactory)) {
            // Factories are wrapped in a closure in order to enforce return type safety.
            $responseFactory = new CallableResponseFactoryDecorator(
                function () use ($responseFactory): ResponseInterface {
                    return $responseFactory();
                }
            );
        }

        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        if (! $routeResult instanceof RouteResult || ! $routeResult->isMethodFailure()) {
            return $handler->handle($request);
        }

        $allowedMethods = $routeResult->getAllowedMethods();
        assert(is_array($allowedMethods));

        return $this->responseFactory->createResponse(StatusCode::STATUS_METHOD_NOT_ALLOWED)
            ->withHeader('Allow', implode(',', $allowedMethods));
    }

    /**
     * @internal This method is only available for unit tests.
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }
}
