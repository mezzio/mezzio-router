<?php

declare(strict_types=1);

namespace Mezzio\Router\Exception;

use function sprintf;

final class RouteCannotBeFoundException extends InvalidArgumentException
{
    public static function withName(string $name): self
    {
        return new self(sprintf(
            'A route with the name "%s" has not been registered',
            $name
        ));
    }
}
