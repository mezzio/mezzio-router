<?php

declare(strict_types=1);

namespace Mezzio\Router\Exception;

use RuntimeException as PhpRuntimeException;

class RuntimeException extends PhpRuntimeException implements ExceptionInterface
{
}
