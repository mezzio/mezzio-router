<?php

declare(strict_types=1);

namespace Mezzio\Router\Test;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class ResponseFactory implements ResponseFactoryInterface
{
    public function __construct(
        public readonly ?ResponseInterface $defaultResponse = null
    ) {
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->defaultResponse
            ? $this->defaultResponse->withStatus($code, $reasonPhrase)
            : (new Response())->withStatus($code, $reasonPhrase);
    }
}
