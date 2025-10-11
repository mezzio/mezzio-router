<?php

declare(strict_types=1);

namespace Mezzio\Router\Exception;

use RuntimeException as PhpRuntimeException;

/**
 * Generic RuntimeException
 *
 * This exception class is extended in router implementation packages.
 *
 * @psalm-suppress ClassMustBeFinal
 */
class RuntimeException extends PhpRuntimeException implements ExceptionInterface
{
}
