<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
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
 * Handle implicit OPTIONS requests.
 *
 * Place this middleware after the routing middleware so that it can handle
 * implicit OPTIONS requests: requests where OPTIONS is used, but the route
 * does not explicitly handle that request method.
 *
 * When invoked, it will create a response with status code 200 and an Allow
 * header that defines all accepted request methods.
 *
 * You may optionally pass a response prototype to the constructor; when
 * present, that prototype will be used to create a new response with the
 * Allow header.
 *
 * The middleware is only invoked in these specific conditions:
 *
 * - an OPTIONS request
 * - with a `RouteResult` present
 * - where the `RouteResult` contains a `Route` instance
 * - and the `Route` instance defines implicit OPTIONS.
 *
 * In all other circumstances, it will return the result of the delegate.
 *
 * @final
 */
class ImplicitOptionsMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /**
     * @param (callable():ResponseInterface)|ResponseFactoryInterface $responseFactory A factory capable of returning an
     *     empty ResponseInterface instance to return for implicit OPTIONS
     *     requests.
     */
    public function __construct($responseFactory)
    {
        if (is_callable($responseFactory)) {
            // Factories are wrapped in a closure in order to enforce return type safety.
            $responseFactory = new CallableResponseFactoryDecorator(
                static function () use ($responseFactory): ResponseInterface {
                    return $responseFactory();
                }
            );
        }

        $this->responseFactory = $responseFactory;
    }

    /**
     * Handle an implicit OPTIONS request.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() !== RequestMethod::METHOD_OPTIONS) {
            return $handler->handle($request);
        }

        $result = $request->getAttribute(RouteResult::class);
        if (! $result instanceof RouteResult) {
            return $handler->handle($request);
        }

        if ($result->isFailure() && ! $result->isMethodFailure()) {
            return $handler->handle($request);
        }

        if ($result->getMatchedRoute()) {
            return $handler->handle($request);
        }

        $allowedMethods = $result->getAllowedMethods();
        assert(is_array($allowedMethods));

        return $this->responseFactory->createResponse()->withHeader('Allow', implode(',', $allowedMethods));
    }
}
