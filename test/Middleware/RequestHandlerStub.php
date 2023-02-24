<?php

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use BadMethodCallException;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestHandlerStub implements RequestHandlerInterface
{
    private ServerRequestInterface|null $received;
    private ResponseInterface $response;

    public function __construct(ResponseInterface|null $response = null)
    {
        $this->response = $response ?: new TextResponse('Default Response');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->received = $request;

        return $this->response;
    }

    public function didExecute(): bool
    {
        return $this->received !== null;
    }

    public function receivedRequest(): ServerRequestInterface
    {
        if (! $this->received) {
            throw new BadMethodCallException('A request has not yet been received');
        }

        return $this->received;
    }
}
