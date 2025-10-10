<?php

declare(strict_types=1);

namespace Mezzio\Router\Exception;

use RuntimeException as PhpRuntimeException;

/** @final */
class RuntimeException extends PhpRuntimeException implements ExceptionInterface
{
}
