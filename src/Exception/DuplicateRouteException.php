<?php

declare(strict_types=1);

namespace Mezzio\Router\Exception;

use DomainException;

/** @final */
class DuplicateRouteException extends DomainException implements
    ExceptionInterface
{
}
