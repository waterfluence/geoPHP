<?php

/**
 * This file contains the CollectionTest class.
 * For more information see the class description below.
 *
 * @author Peter Bathory <peter.bathory@cartographia.hu>
 * @since 2020-03-19
 */

namespace geoPHP\Tests\Geometry;

use geoPHP\Geometry\Collection;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of abstracts Collection class
 *
 * @coversDefaultClass geoPHP\Geometry\Collection
 *
 * @uses geoPHP\Geometry\Point
 * @uses geoPHP\Geometry\LineString
 */
class CollectionTest extends TestCase
{
    public function providerIs3D()
    {
        return [
                [[new Point(1, 2)], false],
                [[new Point(1, 2, 3)], true],
                [[new Point(1, 2, 3), new Point(1, 2)], true],
        ];
    }

    /**
     * @dataProvider providerIs3D
     * @covers ::is3D
     */
    public function testIs3D(array $components, bool $result)
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertEquals($result, $stub->is3D());
    }

    public function providerIsMeasured()
    {
        return [
                [[new Point()], false],
                [[new Point(1, 2)], false],
                [[new Point(1, 2, 3)], false],
                [[new Point(1, 2, 3, 4)], true],
                [[new Point(1, 2, 3, 4), new Point(1, 2)], true],
        ];
    }

    /**
     * @dataProvider providerIsMeasured
     * @covers ::isMeasured
     */
    public function testIsMeasured(array $components, bool $result)
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertEquals($result, $stub->isMeasured());
    }

    public function providerIsEmpty()
    {
        return [
                [[], true],
                [[new Point()], true],
                [[new Point(1, 2)], false],
        ];
    }

    /**
     * @dataProvider providerIsEmpty
     * @covers ::isEmpty
     */
    public function testIsEmpty(array $components, bool $result)
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertEquals($result, $stub->isEmpty());
    }

    /**
     * @covers ::x
     * @covers ::y
     * @covers ::z
     * @covers ::m
     */
    public function testNonApplicableMethods()
    {
        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [[], Geometry::class, true]);

        $this->assertNull($stub->x());
        $this->assertNull($stub->y());
        $this->assertNull($stub->z());
        $this->assertNull($stub->m());
    }

    /**
     * @covers ::asArray
     */
    public function testAsArray()
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
        $stub = $this->getMockForAbstractClass(Collection::class, [$components, Geometry::class, true]);

        $this->assertEquals($expected, $stub->asArray());
    }

    /**
     * @covers ::flatten
     */
    public function testFlatten()
    {
        $components = [
                new Point(1, 2, 3, 4),
                new Point(5, 6, 7, 8),
                new LineString([new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)]),
        ];

        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components]);
        $stub->flatten();

        $this->assertFalse($stub->is3D());
        $this->assertFalse($stub->isMeasured());
        $this->assertFalse($stub->getPoints()[0]->is3D());
    }

    /**
     * @covers ::explode
     */
    public function testExplode()
    {
        $points = [new Point(1, 2), new Point(3, 4), new Point(5, 6), new Point(1, 2)];
        $components = [
                new \geoPHP\Geometry\Polygon([new LineString($points)])
        ];

        /** @var Collection $stub */
        $stub = $this->getMockForAbstractClass(Collection::class, [$components]);

        $segments = $stub->explode();
        $this->assertCount(count($points) - 1, $segments);
        foreach ($segments as $i => $segment) {
            $this->assertCount(2, $segment->getComponents());

            $this->assertSame($points[$i], $segment->startPoint());
            $this->assertSame($points[$i + 1], $segment->endPoint());
        }
    }

    // public function providerDistance()
    // {
    //     return [
    //         "collection of points to empty point" => [
    //             [new Point(1, 1), new Point(2,2)],
    //             new Point(),
    //             null
    //         ],
    //         "collection of points to point" => [
    //             [new Point(1, 1), new Point(2, 1)],
    //             new Point(1, 0),
    //             1.0
    //         ],
    //         "collection of points to touching point" => [
    //             [new Point(1, 1), new Point(2, 1)],
    //             new Point(2, 1),
    //             0.0
    //         ],
    //         "collection of points to touching point in between" => [
    //             [new Point(1, 1), new Point(2, 1)],
    //             new Point(1.5, 1),
    //             0.0
    //         ],
    //         "collection of points to linestring" => [
    //             [new Point(1, 1), new Point(2, 1)],
    //             LineString::fromArray([[0, 0], [2, 0]]),
    //             1.0
    //         ],
    //         "collection of points to crossing linestring" => [
    //             [new Point(0, 1), new Point(4, 1)],
    //             LineString::fromArray([[2, 0], [2, 2]]),
    //             0.0
    //         ],
    //     ];
    // }

    // /**
    //  * @dataProvider providerDistance
    //  * @covers ::distance
    //  */
    // public function testDistance($components, $otherGeometry, $expectedDistance)
    // {
    //     /** @var Collection $stub */
    //     $stub = $this->getMockForAbstractClass(Collection::class, [$components]);

    //     $this->assertSame($expectedDistance, $stub->distance($otherGeometry));
    // }
}
