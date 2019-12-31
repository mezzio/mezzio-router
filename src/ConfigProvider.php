<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Router;

class ConfigProvider
{
    /**
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        // @codingStandardsIgnoreStart
        return [
            // Legacy Zend Framework aliases
            'aliases' => [
                \Zend\Expressive\Router\Middleware\DispatchMiddleware::class => Middleware\DispatchMiddleware::class,
                \Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware::class => Middleware\ImplicitHeadMiddleware::class,
                \Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware::class => Middleware\ImplicitOptionsMiddleware::class,
                \Zend\Expressive\Router\Middleware\RouteMiddleware::class => Middleware\RouteMiddleware::class,
            ],
            'factories' => [
                Middleware\DispatchMiddleware::class         => Middleware\DispatchMiddlewareFactory::class,
                Middleware\ImplicitHeadMiddleware::class     => Middleware\ImplicitHeadMiddlewareFactory::class,
                Middleware\ImplicitOptionsMiddleware::class  => Middleware\ImplicitOptionsMiddlewareFactory::class,
                Middleware\RouteMiddleware::class            => Middleware\RouteMiddlewareFactory::class,
            ]
        ];
        // @codingStandardsIgnoreEnd
    }
}
