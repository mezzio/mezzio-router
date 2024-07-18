<?php

declare(strict_types=1);

namespace MezzioTest\Router\Asset;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

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
