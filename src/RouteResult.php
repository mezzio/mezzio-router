<?php

declare(strict_types=1);

namespace Mezzio\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

/**
 * Value object representing the results of routing.
 *
 * RouterInterface::match() is defined as returning a RouteResult instance,
 * which will contain the following state:
 *
 * - isSuccess()/isFailure() indicate whether routing succeeded or not.
 * - On success, it will contain:
 *   - the matched route name (typically the path)
 *   - the matched route middleware
 *   - any parameters matched by routing
 * - On failure:
 *   - isMethodFailure() further qualifies a routing failure to indicate that it
 *     was due to using an HTTP method not allowed for the given path.
 *   - If the failure was due to HTTP method negotiation, it will contain the
 *     list of allowed HTTP methods.
 *
 * RouteResult instances are consumed by the Application in the routing
 * middleware.
 */
final class RouteResult implements MiddlewareInterface
{
    /**
     * Only allow instantiation via factory methods.
     *
     * @param bool $success Success state of routing.
     * @param Route|null $route Route matched during routing.
     * @param null|string $matchedRouteName The name of the matched route. Null if routing failed.
     * @param null|list<string> $allowedMethods Methods allowed by the route, or null if all methods are allowed.
     * @param array<string, mixed> $matchedParams Matched routing parameters for successful routes.
     */
    private function __construct(
        private readonly bool $success,
        private readonly ?Route $route,
        private readonly ?string $matchedRouteName,
        private readonly ?array $allowedMethods,
        private readonly array $matchedParams = [],
    ) {
    }

    /**
     * Create an instance representing a route success from the matching route.
     *
     * @param array<string, mixed> $params Parameters associated with the matched route, if any.
     */
    public static function fromRoute(Route $route, array $params = []): self
    {
        return new self(
            true,
            $route,
            $route->getName(),
            $route->getAllowedMethods(),
            $params,
        );
    }

    /**
     * Create an instance representing a route failure.
     *
     * @param null|list<string> $methods HTTP methods allowed for the current URI, if any.
     *     null is equivalent to allowing any HTTP method; empty array means none.
     */
    public static function fromRouteFailure(?array $methods): self
    {
        return new self(
            false,
            null,
            null,
            $methods,
            [],
        );
    }

    /**
     * Process the result as middleware.
     *
     * If the result represents a failure, it passes handling to the handler.
     *
     * Otherwise, it processes the composed middleware using the provided request
     * and handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isFailure()) {
            return $handler->handle($request);
        }

        $route = $this->getMatchedRoute();
        assert($route instanceof MiddlewareInterface);

        return $route->process($request, $handler);
    }

    /**
     * Does the result represent successful routing?
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Retrieve the route that resulted in the route match.
     *
     * @return false|Route false if representing a routing failure; Route instance otherwise.
     */
    public function getMatchedRoute(): Route|false
    {
        return $this->route ?? false;
    }

    /**
     * Retrieve the matched route name, if possible.
     *
     * If this result represents a failure, return false; otherwise, return the
     * matched route name.
     */
    public function getMatchedRouteName(): false|string
    {
        if ($this->isFailure()) {
            return false;
        }

        assert($this->matchedRouteName !== null);

        return $this->matchedRouteName;
    }

    /**
     * Returns the matched params.
     *
     * Guaranteed to return an array, even if it is simply empty.
     *
     * @return array<string, mixed>
     */
    public function getMatchedParams(): array
    {
        return $this->matchedParams;
    }

    /**
     * Is this a routing failure result?
     */
    public function isFailure(): bool
    {
        return ! $this->success;
    }

    /**
     * Does the result represent failure to route due to HTTP method?
     */
    public function isMethodFailure(): bool
    {
        if ($this->isSuccess() || $this->allowedMethods === Route::HTTP_METHOD_ANY) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve the allowed methods for the route failure.
     *
     * @return null|list<string> HTTP methods allowed
     */
    public function getAllowedMethods(): ?array
    {
        if ($this->isSuccess()) {
            assert($this->route !== null);
            return $this->route->getAllowedMethods();
        }

        return $this->allowedMethods;
    }
}
