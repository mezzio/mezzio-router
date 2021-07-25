<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Router\RouteResult as ZendExpressiveRouteResult;

/**
 * Default routing middleware.
 *
 * Uses the composed router to match against the incoming request, and
 * injects the request passed to the handler with the `RouteResult` instance
 * returned (using the `RouteResult` class name as the attribute name).
 *
 * If routing succeeds, injects the request passed to the handler with any
 * matched parameters as well.
 */
class RouteMiddleware implements MiddlewareInterface
{
    /** @var RouterInterface */
    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->router->match($request);

        // Inject the actual route result, as well as individual matched parameters.
        $request = $request
            ->withAttribute(RouteResult::class, $result)
            ->withAttribute(ZendExpressiveRouteResult::class, $result);

        if ($result->isSuccess()) {
            foreach ($result->getMatchedParams() as $param => $value) {
                $request = $request->withAttribute($param, $value);
            }
        }

        return $handler->handle($request);
    }

    /**
     * @internal This should only be used by unit tests.
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
