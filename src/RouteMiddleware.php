<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Router;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webimpress\HttpMiddlewareCompatibility\HandlerInterface;
use Webimpress\HttpMiddlewareCompatibility\MiddlewareInterface;

use const Webimpress\HttpMiddlewareCompatibility\HANDLER_METHOD;

/**
 * Default routing middleware.
 *
 * Uses the composed router to match against the incoming request.
 *
 * When routing failure occurs, if the failure is due to HTTP method, uses
 * the composed response prototype to generate a 405 response; otherwise,
 * it delegates to the next middleware.
 *
 * If routing succeeds, injects the route result into the request (under the
 * RouteResult class name), as well as any matched parameters, before
 * delegating to the next middleware.
 */
class RouteMiddleware implements MiddlewareInterface
{
    /**
     * Response prototype for 405 responses.
     *
     * @var ResponseInterface
     */
    private $responsePrototype;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     * @param ResponseInterface $responsePrototype
     */
    public function __construct(RouterInterface $router, ResponseInterface $responsePrototype)
    {
        $this->router = $router;
        $this->responsePrototype = $responsePrototype;
    }

    /**
     * @param ServerRequestInterface $request
     * @param HandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, HandlerInterface $handler)
    {
        $result = $this->router->match($request);

        if ($result->isFailure()) {
            if ($result->isMethodFailure()) {
                return $this->responsePrototype->withStatus(StatusCode::STATUS_METHOD_NOT_ALLOWED)
                    ->withHeader('Allow', implode(',', $result->getAllowedMethods()));
            }
            return $handler->{HANDLER_METHOD}($request);
        }

        // Inject the actual route result, as well as individual matched parameters.
        $request = $request->withAttribute(RouteResult::class, $result)->withAttribute(\Zend\Expressive\Router\RouteResult::class, $result);
        foreach ($result->getMatchedParams() as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }

        return $handler->{HANDLER_METHOD}($request);
    }
}
