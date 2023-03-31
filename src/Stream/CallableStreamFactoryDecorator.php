<?php

declare(strict_types=1);

namespace Mezzio\Router\Stream;

use Mezzio\Router\Exception\RuntimeException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 * @deprecated Will be removed in version 4.0.0
 */
final class CallableStreamFactoryDecorator implements StreamFactoryInterface
{
    /** @var callable(): StreamInterface */
    private $streamFactory;

    /** @param callable(): StreamInterface $streamFactory */
    public function __construct(callable $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    /** @inheritDoc */
    public function createStream(string $content = ''): StreamInterface
    {
        return ($this->streamFactory)();
    }

    /** @inheritDoc */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        throw new RuntimeException('This method will not be implemented');
    }

    /** @inheritDoc */
    public function createStreamFromResource($resource): StreamInterface
    {
        throw new RuntimeException('This method will not be implemented');
    }
}
