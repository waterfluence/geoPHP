<?php

declare(strict_types=1);

namespace geoPHP\Tests\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of LineString geometry
 *
 *
 */
#[\PHPUnit\Framework\Attributes\Group('geometry')]
final class LineStringTest extends TestCase
{

    /**
     * @return mixed[]
     */
    private function createPoints(array $coordinateArray): array
    {
        $points = [];
        foreach ($coordinateArray as $point) {
            $points[] = Point::fromArray($point);
        }
        return $points;
    }

    public static function providerValidComponents(): \Iterator
    {
        yield 'empty' => [[]];
        yield 'with two points' => [[[0, 0], [1, 1]]];
        yield 'LineString Z' => [[[0, 0, 0], [1, 1, 1]]];
        yield 'LineString M' => [[[0, 0, null, 0], [1, 1, null, 1]]];
        yield 'LineString ZM' => [[[0, 0, 0, 0], [1, 1, 1, 1]]];
        yield 'LineString with 5 points' => [[[0, 0], [1, 1], [2, 2], [3, 3], [4, 4]]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidComponents')]
    public function testConstructor(array $points): void
    {
        $this->assertNotNull(new LineString($this->createPoints($points)));
    }

    public function testConstructorEmptyComponentThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot create a collection of empty Points.+/');

        // Empty points
        new LineString([new Point(), new Point(), new Point()]);
    }

    public function testConstructorNonArrayComponentThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Component geometries must be passed as array/');

        new LineString('foo');
    }

    public function testConstructorSinglePointThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot construct a [a-zA-Z_\\\\]+LineString with a single point/');

        new LineString([new Point(1, 2)]);
    }

    public function testConstructorWrongComponentTypeThrowsException(): void
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageMatches('/Cannot create a collection of [a-zA-Z_\\\\]+ components, expected type is.+/');

        new LineString([new LineString(), new LineString()]);
    }

    public function testFromArray(): void
    {
        $this->assertEquals(
                LineString::fromArray([[1,2,3,4], [5,6,7,8]]),
                new LineString([new Point(1,2,3,4), new Point(5,6,7,8)])
        );
    }

    public function testGeometryType(): void
    {
        $line = new LineString();

        $this->assertEquals(LineString::LINE_STRING, $line->geometryType());

        $this->assertInstanceOf(LineString::class, $line);
        $this->assertInstanceOf(\geoPHP\Geometry\Curve::class, $line);
        $this->assertInstanceOf(\geoPHP\Geometry\Collection::class, $line);
        $this->assertInstanceOf(\geoPHP\Geometry\Geometry::class, $line);
    }

    public function testIsEmpty(): void
    {
        $line1 = new LineString();
        $this->assertTrue($line1->isEmpty());

        $line2 = new LineString($this->createPoints([[1,2], [3,4]]));
        $this->assertFalse($line2->isEmpty());
    }

    public function testDimension(): void
    {
        $this->assertSame(1, (new LineString())->dimension());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidComponents')]
    public function testNumPoints(array $points): void
    {
        $line = new LineString($this->createPoints($points));
        $this->assertCount($line->numPoints(), $points);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidComponents')]
    public function testPointN(array $points): void
    {
        $components = $this->createPoints($points);
        $line = new LineString($components);

        $this->assertNotInstanceOf(\geoPHP\Geometry\Point::class, $line->pointN(0));
        $counter = count($components);

        for ($i=1; $i < $counter; $i++) {
            // positive n
            $this->assertEquals($components[$i-1], $line->pointN($i));

            // negative n
            $this->assertEquals($components[count($components)-$i], $line->pointN(-$i));
        }
    }

    public static function providerCentroid(): \Iterator
    {
        yield 'empty LineString' => [[], new Point()];
        yield 'null coordinates' => [[[0, 0], [0, 0]], new Point(0, 0)];
        yield '↗ vector' => [[[0, 0], [1, 1]], new Point(0.5, 0.5)];
        yield '↙ vector' => [[[0, 0], [-1, -1]], new Point(-0.5, -0.5)];
        yield 'random geographical coordinates' => [[
                [20.0390625, -16.97274101999901],
                [-11.953125, 17.308687886770034],
                [0.703125, 52.696361078274485],
                [30.585937499999996, 52.696361078274485],
                [42.5390625, 41.77131167976407],
                [-13.359375, 38.8225909761771],
                [18.984375, 17.644022027872726]
        ], new Point(8.717980875505775, 31.130453138673776)];
        yield 'crossing the antimeridian' => [[[170, 47], [-170, 47]], new Point(0, 47)];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerCentroid')]
    public function testCentroid(array $points, \geoPHP\Geometry\Point $centroidPoint): void
    {
        $line = LineString::fromArray($points);
        $centroid = $line->centroid();
        $centroid->setGeos(null);

        $this->assertEquals($centroidPoint, $centroid);
    }

    public static function providerIsSimple(): \Iterator
    {
        yield 'simple' => [[[0, 0], [0, 10]], true];
        yield 'self-crossing' => [[[0, 0], [10, 0], [10, 10], [0, -10]], false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerIsSimple')]
    public function testIsSimple(array $points, bool $result): void
    {
        $line = LineString::fromArray($points);

        $this->assertSame($line->isSimple(), $result);
    }

    public static function providerLength(): \Iterator
    {
        yield [[[0, 0], [10, 0]], 10.0];
        yield [[[1, 1], [2, 2], [2, 3.5], [1, 3], [1, 2], [2, 1]], 6.446461113496085];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerLength')]
    public function testLength(array $points, float $result): void
    {
        $line = LineString::fromArray($points);

        $this->assertSame($line->length(), $result);
    }

    public static function providerLength3D(): \Iterator
    {
        yield [[[0, 0, 0], [10, 0, 10]], 14.142135623730951];
        yield [[[1, 1, 0], [2, 2, 2], [2, 3.5, 0], [1, 3, 2], [1, 2, 0], [2, 1, 2]], 11.926335310544065];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerLength3D')]
    public function testLength3D(array $points, float $result): void
    {
        $line = LineString::fromArray($points);

        $this->assertSame($line->length3D(), $result);
    }

    public static function providerLengths(): \Iterator
    {
        yield [[[0, 0], [0, 0]], [
                'greatCircle' => 0.0,
                'haversine'   => 0.0,
                'vincenty'    => 0.0,
                'PostGIS'     => 0.0
        ]];
        yield [[[0, 0], [10, 0]], [
                'greatCircle' => 1113194.9079327357,
                'haversine'   => 1113194.9079327371,
                'vincenty'    => 1113194.9079322326,
                'PostGIS'     => 1113194.90793274
        ]];
        yield [[[0, 0, 0], [10, 0, 5000]], [
                'greatCircle' => 1113206.136817154,
                'haversine'   => 1113194.9079327371,
                'vincenty'    => 1113194.9079322326,
                'PostGIS'     => 1113194.90793274
        ]];
        yield [[[0, 47], [10, 47]], [
                'greatCircle' => 758681.06593496865,
                'haversine'   => 758681.06593497901,
                'vincenty'    => 760043.0186457854,
                'postGIS'     => 760043.018642104
        ]];
        yield [[[1, 1, 0], [2, 2, 2], [2, 3.5, 0], [1, 3, 2], [1, 2, 0], [2, 1, 2]], [
                'greatCircle' => 717400.38999229996,
                'haversine'   => 717400.38992081373,
                'vincenty'    => 714328.06433538091,
                'postGIS'     => 714328.064406871
        ]];
        yield [[[19, 47], [19.000001, 47], [19.000001, 47.000001], [19.000001, 47.000002], [19.000002, 47.000002]], [
                'greatCircle' => 0.37447839912084818,
                'haversine'   => 0.36386002147417207,
                'vincenty'    => 0.37445330532190713,
                'postGIS'     => 0.374453678675281
        ]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerLengths')]
    public function testGreatCircleLength(array $points, array $results): void
    {
        $line = LineString::fromArray($points);

        $this->assertEqualsWithDelta($line->greatCircleLength(), $results['greatCircle'], 1e-8);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerLengths')]
    public function testHaversineLength(array $points, array $results): void
    {
        $line = LineString::fromArray($points);

        $this->assertEqualsWithDelta($line->haversineLength(), $results['haversine'], 1e-7);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerLengths')]
    public function testVincentyLength(array $points, array $results): void
    {
        $line = LineString::fromArray($points);

        $this->assertEqualsWithDelta($line->vincentyLength(), $results['vincenty'], 1e-8);
    }

    public function testVincentyLengthAntipodalPoints(): void
    {
        $line = LineString::fromArray([[-89.7, 0], [89.7, 0]]);

        $this->assertNull($line->vincentyLength());
    }

    public function testExplode(): void
    {
        $point1 = new Point(1, 2);
        $point2 = new Point(3, 4);
        $point3 = new Point(5, 6);
        $line = new LineString([$point1, $point2, $point3]);

        $this->assertEquals($line->explode(), [new LineString([$point1, $point2]), new LineString([$point2, $point3])]);

        $this->assertSame($line->explode(true), [[$point1, $point2], [$point2, $point3]]);

        $this->assertSame((new LineString())->explode(), []);

        $this->assertSame((new LineString())->explode(true), []);
    }

    public static function providerDistance(): \Iterator
    {
        yield 'Point on vertex' => [new Point(0, 10), 0.0];
        yield 'Point, closest distance is 10' => [new Point(10, 10), 10.0];
        yield 'LineString, same points' => [LineString::fromArray([[0, 10], [10, 10]]), 0.0];
        yield 'LineString, closest distance is 10' => [LineString::fromArray([[10, 10], [20, 20]]), 10.0];
        yield 'intersecting line' => [LineString::fromArray([[-10, 5], [10, 5]]), 0.0];
        yield 'GeometryCollection' => [new \geoPHP\Geometry\GeometryCollection([LineString::fromArray([[10, 10], [20, 20]])]), 10.0];
    }

    /**
     * @param Geometry $otherGeometry
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerDistance')]
    public function testDistance($otherGeometry, float $expectedDistance): void
    {
        $line = LineString::fromArray([[0, 0], [0, 10]]);

        $this->assertSame($line->distance($otherGeometry), $expectedDistance);
    }

    public function testMinimumAndMaximumZAndMAndDifference(): void
    {
        $line = LineString::fromArray([[0, 0, 100.0, 0.0], [1, 1, 50.0, -0.5], [2, 2, 150.0, -1.0], [3, 3, 75.0, 0.5]]);

        $this->assertEqualsWithDelta(50.0, $line->minimumZ(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(150.0, $line->maximumZ(), PHP_FLOAT_EPSILON);

        $this->assertSame($line->minimumM(), -1.0);
        $this->assertEqualsWithDelta(0.5, $line->maximumM(), PHP_FLOAT_EPSILON);

        $this->assertEqualsWithDelta(25.0, $line->zDifference(), PHP_FLOAT_EPSILON);
        $this->assertNull(LineString::fromArray([[0, 1], [2, 3]])->zDifference());
    }

    /**
     * @return \Iterator<(int | string), array<mixed>> [tolerance, gain, loss]
     */
    public static function providerElevationGainAndLossByTolerance(): \Iterator
    {
        yield [null, 50.0, 30.0];
        yield [0, 50.0, 30.0];
        yield [5, 48.0, 28.0];
        yield [15, 36.0, 16.0];
    }

    /**
     * @param float|null $tolerance
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerElevationGainAndLossByTolerance')]
    public function testElevationGainAndLoss(?int $tolerance, float $gain, float $loss): void
    {
        $line = LineString::fromArray(
                [[0, 0, 100], [0, 0, 102], [0, 0, 105], [0, 0, 103], [0, 0, 110], [0, 0, 118],
                [0, 0, 102], [0, 0, 108], [0, 0, 102], [0, 0, 108], [0, 0, 102], [0, 0, 120] ]
        );

        $this->assertSame($line->elevationGain($tolerance), $gain);

        $this->assertSame($line->elevationLoss($tolerance), $loss);
    }
}
