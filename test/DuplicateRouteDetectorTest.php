<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Mezzio\Router\DuplicateRouteDetector;
use Mezzio\Router\Exception\DuplicateRouteException;
use Mezzio\Router\Route;
use MezzioTest\Router\Asset\NoOpMiddleware;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress InternalClass,InternalMethod */
class DuplicateRouteDetectorTest extends TestCase
{
    private DuplicateRouteDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new DuplicateRouteDetector();
    }

    public function test2RoutesWithTheSameNameAreExceptional(): void
    {
        $a = new Route('/a', new NoOpMiddleware(), Route::HTTP_METHOD_ANY, 'name');
        $b = new Route('/b', new NoOpMiddleware(), Route::HTTP_METHOD_ANY, 'name');

        $this->detector->detectDuplicate($a);

        $this->expectException(DuplicateRouteException::class);

        $this->detector->detectDuplicate($b);
    }

    public function testRoutesOnTheSamePathButWithDifferentMethodsAreNotDuplicates(): void
    {
        $a = new Route('/a', new NoOpMiddleware(), ['GET'], 'a');
        $b = new Route('/a', new NoOpMiddleware(), ['POST'], 'b');
        $this->detector->detectDuplicate($a);
        $this->detector->detectDuplicate($b);

        $this->expectNotToPerformAssertions();
    }

    public function testRoutesOnTheSamePathAreExceptional(): void
    {
        $a = new Route('/a', new NoOpMiddleware(), ['GET'], 'a');
        $b = new Route('/a', new NoOpMiddleware(), ['GET'], 'b');
        $this->detector->detectDuplicate($a);

        $this->expectException(DuplicateRouteException::class);

        $this->detector->detectDuplicate($b);
    }

    public function testDuplicateRoutesOnTheSamePathWithMethodIntersectionIsExceptional(): void
    {
        $a = new Route('/a', new NoOpMiddleware(), ['GET'], 'a');
        $b = new Route('/a', new NoOpMiddleware(), Route::HTTP_METHOD_ANY, 'b');
        $this->detector->detectDuplicate($a);

        $this->expectException(DuplicateRouteException::class);

        $this->detector->detectDuplicate($b);
    }

    public function testDuplicateRoutesOnTheSamePathBothWithAnyMethodAreExceptional(): void
    {
        $a = new Route('/a', new NoOpMiddleware(), Route::HTTP_METHOD_ANY, 'a');
        $b = new Route('/a', new NoOpMiddleware(), Route::HTTP_METHOD_ANY, 'b');
        $this->detector->detectDuplicate($a);

        $this->expectException(DuplicateRouteException::class);

        $this->detector->detectDuplicate($b);
    }
}
