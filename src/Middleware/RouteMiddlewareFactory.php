<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Mezzio\Router\Exception\MissingDependencyException;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * Create and return a RouteMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - Mezzio\Router\RouterInterface, which should resolve to
 *   a class implementing that interface.
 *
 * @final
 */
class RouteMiddlewareFactory
{
    private string $routerServiceName;

    /**
     * Allow serialization
     *
     * @param array{routerServiceName?: string} $data
     */
    public static function __set_state(array $data): self
    {
        return new self(
            $data['routerServiceName'] ?? RouterInterface::class
        );
    }

    /**
     * Provide the name of the router service to use when creating the route
     * middleware.
     */
    public function __construct(string $routerServiceName = RouterInterface::class)
    {
        $this->routerServiceName = $routerServiceName;
    }

    /**
     * @throws MissingDependencyException If the RouterInterface service is missing.
     */
    public function __invoke(ContainerInterface $container): RouteMiddleware
    {
        if (! $container->has($this->routerServiceName)) {
            throw MissingDependencyException::dependencyForService(
                $this->routerServiceName,
                RouteMiddleware::class
            );
        }

        $router = $container->get($this->routerServiceName);
        assert($router instanceof RouterInterface);

        return new RouteMiddleware($router);
    }

    /**
     * @internal This should only be used by unit tests.
     */
    public function getRouterServiceName(): string
    {
        return $this->routerServiceName;
    }
}
