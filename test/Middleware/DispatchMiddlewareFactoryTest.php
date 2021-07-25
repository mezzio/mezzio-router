<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Middleware\DispatchMiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DispatchMiddlewareFactoryTest extends TestCase
{
    public function testFactoryProducesDispatchMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::never())
            ->method(self::anything());

        $factory = new DispatchMiddlewareFactory();
        $factory($container);
    }
}
