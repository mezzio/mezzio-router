<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Router;

use ArrayObject;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface as ZendExpressiveRouterInterface;

use function array_key_exists;

/**
 * Create and return a RouteCollector instance.
 *
 * This factory depends on one other service:
 *
 * - Mezzio\Router\RouterInterface, which should resolve to
 *   a class implementing that interface.
 */
class RouteCollectorFactory
{
    /**
     * @throws Exception\MissingDependencyException If the RouterInterface service is
     *     missing.
     */
    public function __invoke(ContainerInterface $container): RouteCollector
    {
        if (
            ! $container->has(RouterInterface::class)
            && ! $container->has(ZendExpressiveRouterInterface::class)
        ) {
            throw Exception\MissingDependencyException::dependencyForService(
                RouterInterface::class,
                RouteCollector::class
            );
        }

        return new RouteCollector(
            $container->has(RouterInterface::class)
                ? $container->get(RouterInterface::class)
                : $container->get(ZendExpressiveRouterInterface::class),
            $this->getDetectDuplicatesFlag($container)
        );
    }

    private function getDetectDuplicatesFlag(ContainerInterface $container): bool
    {
        if (! $container->has('config')) {
            return true;
        }

        $config = $container->get('config');

        $config = $config instanceof ArrayObject
            ? $config->getArrayCopy()
            : $config;

        if (! array_key_exists(RouteCollector::class, $config)) {
            return true;
        }

        $config = $config[RouteCollector::class];
        if (! array_key_exists('detect_duplicates', $config)) {
            return true;
        }

        return (bool) $config['detect_duplicates'];
    }
}
