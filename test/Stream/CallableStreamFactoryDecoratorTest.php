<?php

declare(strict_types=1);

namespace MezzioTest\Router\Stream;

use Laminas\Diactoros\StreamFactory;
use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\Stream\CallableStreamFactoryDecorator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

use function fopen;

/**
 * @psalm-suppress InternalClass, InternalMethod, DeprecatedClass
 */
#[CoversClass(CallableStreamFactoryDecorator::class)]
class CallableStreamFactoryDecoratorTest extends TestCase
{
    private StreamInterface $stream;
    private CallableStreamFactoryDecorator $decorator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stream = (new StreamFactory())->createStream();

        $this->decorator = new CallableStreamFactoryDecorator(fn (): StreamInterface => $this->stream);
    }

    public function testThatCreateStreamWillProduceStream(): void
    {
        self::assertSame($this->stream, $this->decorator->createStream());
    }

    public function testThatTheStreamDoesNotReceiveContentArgument(): void
    {
        $result = $this->decorator->createStream('some content');

        self::assertSame('', $result->getContents());
    }

    public function testCreateStreamFromFileIsNotImplemented(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This method will not be implemented');

        $this->decorator->createStreamFromFile('/foo');
    }

    public function testCreateStreamFromResourceIsNotImplemented(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This method will not be implemented');

        $this->decorator->createStreamFromResource(fopen(__FILE__, 'r'));
    }
}
