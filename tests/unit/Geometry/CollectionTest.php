<?php

declare(strict_types=1);

/**
 * This file contains the CollectionTest class.
 * For more information see the class description below.
 *
 * @author Peter Bathory <peter.bathory@cartographia.hu>
 * @since 2020-03-19
 */
namespace geoPHP\Tests\Geometry;

use \geoPHP\Geometry\Collection;
use \geoPHP\Geometry\Point;
use \geoPHP\Geometry\LineString;
use \PHPUnit\Framework\TestCase;

/**
 * This class... TODO: Complete this
 */
final class CollectionTest extends TestCase
{

    public static function providerIs3D(): \Iterator
    {
        yield [[new Point(1, 2)], false];
        yield [[new Point(1, 2, 3)], true];
        yield [[new Point(1, 2, 3), new Point(1, 2)], true];
    }

    /**
     * @param Point[] $components
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerIs3D')]
    public function testIs3D(array $components, bool $result): void
    {
        /** @var Collection $stub */
        $stub = new CollectionStub($components, true);

        $this->assertEquals($stub->is3D(), $result);
    }

    public static function providerIsMeasured(): \Iterator
    {
        yield [[new Point()], false];
        yield [[new Point(1, 2)], false];
        yield [[new Point(1, 2, 3)], false];
        yield [[new Point(1, 2, 3, 4)], true];
        yield [[new Point(1, 2, 3, 4), new Point(1, 2)], true];
    }

    /**
     * @param Point[] $components
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerIsMeasured')]
    public function testIsMeasured(array $components, bool $result): void
    {
        /** @var Collection $stub */
        $stub = new CollectionStub($components, true);

        $this->assertEquals($stub->isMeasured(), $result);
    }

    public static function providerIsEmpty(): \Iterator
    {
        yield [[], true];
        yield [[new Point()], true];
        yield [[new Point(1, 2)], false];
    }

    /**
     * @param Point[] $components
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerIsEmpty')]
    public function testIsEmpty(array $components, bool $result): void
    {
        /** @var Collection $stub */
        $stub = new CollectionStub($components, true);

        $this->assertEquals($stub->isEmpty(), $result);
    }

    public function testNonApplicableMethods(): void
    {
        /** @var Collection $stub */
        $stub = new CollectionStub([], true);

        $this->assertNull($stub->x());
        $this->assertNull($stub->y());
        $this->assertNull($stub->z());
        $this->assertNull($stub->m());
    }

    public function testAsArray(): void
    {
        $components = [
                new Point(1, 2),
                new LineString()
        ];
        $expected = [
                [1, 2],
                []
        ];

        /** @var Collection $stub */
        $stub = new CollectionStub($components, true);

        $this->assertEquals($stub->asArray(), $expected);
    }

    public function testFlatten(): void
    {
        $components = [
                new Point(1, 2, 3, 4),
                new Point(5, 6, 7, 8),
                new LineString([new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)]),
        ];

        /** @var Collection $stub */
        $stub = new CollectionStub($components);
        $stub->flatten();

        $this->assertFalse($stub->hasZ());
        $this->assertFalse($stub->isMeasured());
        $this->assertFalse($stub->getPoints()[0]->hasZ());
    }

    public function testExplode(): void
    {
        $points = [new Point(1, 2), new Point(3, 4), new Point(5, 6), new Point(1, 2)];
        $components = [
                new \geoPHP\Geometry\Polygon([new LineString($points)])
        ];

        /** @var Collection $stub */
        $stub = new CollectionStub($components);

        $segments = $stub->explode();
        $this->assertCount(count($points) - 1, $segments);
        foreach ($segments as $i => $segment) {
            $this->assertCount(2, $segment->getComponents());

            $this->assertSame($segment->startPoint(), $points[$i]);
            $this->assertSame($segment->endPoint(), $points[$i + 1]);
        }
    }
}
