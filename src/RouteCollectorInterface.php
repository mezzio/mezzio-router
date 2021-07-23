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
 */
interface RouteCollectorInterface
{
    /**
     * Add a route for the route middleware to match.
     *
     * Accepts a combination of a path and middleware, and optionally the HTTP methods allowed.
     *
     * @param null|array  $methods HTTP method to accept; null indicates any.
     * @param null|string $name    The name of the route.
     * @throws Exception\DuplicateRouteException If specification represents an existing route.
     */
    public function route(
        string $path,
        MiddlewareInterface $middleware,
        ?array $methods = null,
        ?string $name = null
    ): Route;

    /**
     * @param null|string $name The name of the route.
     */
    public function get(string $path, MiddlewareInterface $middleware, ?string $name = null): Route;

    /**
     * @param null|string $name The name of the route.
     */
    public function post(string $path, MiddlewareInterface $middleware, ?string $name = null): Route;

    /**
     * @param null|string $name The name of the route.
     */
    public function put(string $path, MiddlewareInterface $middleware, ?string $name = null): Route;

    /**
     * @param null|string $name The name of the route.
     */
    public function patch(string $path, MiddlewareInterface $middleware, ?string $name = null): Route;

    /**
     * @param null|string $name The name of the route.
     */
    public function delete(string $path, MiddlewareInterface $middleware, ?string $name = null): Route;

    /**
     * @param null|string $name The name of the route.
     */
    public function any(string $path, MiddlewareInterface $middleware, ?string $name = null): Route;

    /**
     * Retrieve all directly registered routes with the application.
     *
     * @return Route[]
     */
    public function getRoutes(): array;
}
