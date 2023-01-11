<?php

declare(strict_types=1);

namespace Mezzio\Router;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Aggregate routes for the router.
 *
 * This class provides * methods for creating path+HTTP method-based routes and
 * injecting them into the router:
 *
 * - get
 * - post
 * - put
 * - patch
 * - delete
 * - any
 *
 * A general `route()` method allows specifying multiple request methods and/or
 * arbitrary request methods when creating a path-based route.
 *
 * Internally, the class performs some checks for duplicate routes when
 * attaching via one of the exposed methods, and will raise an exception when a
 * collision occurs.
 *
 * @final
 */
class RouteCollector implements RouteCollectorInterface
{
    /**
     * List of all routes registered directly with the application.
     *
     * @var list<Route>
     */
    private array $routes = [];

    private ?DuplicateRouteDetector $duplicateRouteDetector = null;

    public function __construct(
        protected RouterInterface $router,
        protected bool $detectDuplicates = true
    ) {
    }

    /** @inheritDoc */
    public function route(
        string $path,
        MiddlewareInterface $middleware,
        ?array $methods = null,
        ?string $name = null
    ): Route {
        $methods = $methods ?? Route::HTTP_METHOD_ANY;
        $route   = new Route($path, $middleware, $methods, $name);
        $this->detectDuplicate($route);
        $this->routes[] = $route;
        $this->router->addRoute($route);

        return $route;
    }

    /** @inheritDoc */
    public function get(string $path, MiddlewareInterface $middleware, ?string $name = null): Route
    {
        return $this->route($path, $middleware, ['GET'], $name);
    }

    /** @inheritDoc */
    public function post(string $path, MiddlewareInterface $middleware, ?string $name = null): Route
    {
        return $this->route($path, $middleware, ['POST'], $name);
    }

    /** @inheritDoc */
    public function put(string $path, MiddlewareInterface $middleware, ?string $name = null): Route
    {
        return $this->route($path, $middleware, ['PUT'], $name);
    }

    /** @inheritDoc */
    public function patch(string $path, MiddlewareInterface $middleware, ?string $name = null): Route
    {
        return $this->route($path, $middleware, ['PATCH'], $name);
    }

    /** @inheritDoc */
    public function delete(string $path, MiddlewareInterface $middleware, ?string $name = null): Route
    {
        return $this->route($path, $middleware, ['DELETE'], $name);
    }

    /** @inheritDoc */
    public function any(string $path, MiddlewareInterface $middleware, ?string $name = null): Route
    {
        return $this->route($path, $middleware, null, $name);
    }

    /** @inheritDoc */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    private function detectDuplicate(Route $route): void
    {
        if ($this->detectDuplicates && ! $this->duplicateRouteDetector) {
            $this->duplicateRouteDetector = new DuplicateRouteDetector();
        }

        if ($this->duplicateRouteDetector) {
            $this->duplicateRouteDetector->detectDuplicate($route);
            return;
        }
    }

    /**
     * @internal This should only be used in unit tests.
     */
    public function willDetectDuplicates(): bool
    {
        return $this->detectDuplicates;
    }
}
