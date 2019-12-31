<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Router\Exception;

use Psr\Container\NotFoundExceptionInterface;

class MissingDependencyException extends RuntimeException implements
    ExceptionInterface,
    NotFoundExceptionInterface
{
    /**
     * @param string $dependency
     * @param string $service
     * @return self
     */
    public static function dependencyForService($dependency, $service)
    {
        return new self(sprintf(
            'Missing dependency "%s" for service "%2$s"; please make sure it is'
            . ' registered in your container. Refer to the %2$s class and/or its'
            . ' factory to determine what the service should return.',
            $dependency,
            $service
        ));
    }
}
