<?php

declare(strict_types=1);

namespace Mezzio\Router\Test;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This test asset is used by {@link AbstractImplicitMethodsIntegrationTest} it is not intended for production use.
 *
 * Additionally, this class is not subject to any backwards compatibility guarantees
 *
 * @psalm-internal \MezzioTest
 * @psalm-internal \Mezzio
 */
final class FixedResponseFactory implements ResponseFactoryInterface
{
    public function __construct(public readonly ResponseInterface $response)
    {
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->response->withStatus($code);
    }
}
