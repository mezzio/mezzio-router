<?php

declare(strict_types=1);

namespace Mezzio\Router\Middleware;

use Psr\Container\ContainerInterface;

class DispatchMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): DispatchMiddleware
    {
        return new DispatchMiddleware();
    }
}
