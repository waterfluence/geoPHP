<?php

namespace geoPHP\Tests\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of LineString geometry
 *
 * @group geometry
 *
 */
class LineStringTest extends TestCase
{

    private function createPoints($coordinateArray)
    {
        $points = [];
        foreach ($coordinateArray as $point) {
            $points[] = Point::fromArray($point);
        }
        return $points;
    }

    public function providerValidComponents()
    {
        return [
            'empty' =>
                [[]],
            'with two points' =>
                [[[0, 0], [1, 1]]],
            'LineString Z' =>
                [[[0, 0, 0], [1, 1, 1]]],
            'LineString M' =>
                [[[0, 0, null, 0], [1, 1, null, 1]]],
            'LineString ZM' =>
                [[[0, 0, 0, 0], [1, 1, 1, 1]]],
            'LineString with 5 points' =>
                [[[0, 0], [1, 1], [2, 2], [3, 3], [4, 4]]],
        ];
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param array $points
     */
    public function testConstructor($points)
    {
        $this->assertNotNull(new LineString($this->createPoints($points)));
    }

    public function testConstructorEmptyComponentThrowsException()
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageRegExp('/Cannot create a collection of empty Points.+/');

        // Empty points
        new LineString([new Point(), new Point(), new Point()]);
    }

    public function testConstructorNonArrayComponentThrowsException()
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageRegExp('/Component geometries must be passed as array/');

        new LineString('foo');
    }

    public function testConstructorSinglePointThrowsException()
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageRegExp('/Cannot construct a [a-zA-Z_\\\\]+LineString with a single point/');

        new LineString([new Point(1, 2)]);
    }

    public function testConstructorWrongComponentTypeThrowsException()
    {
        $this->expectException(InvalidGeometryException::class);
        $this->expectExceptionMessageRegExp('/Cannot create a collection of [a-zA-Z_\\\\]+ components, expected type is.+/');

        new LineString([new LineString(), new LineString()]);
    }

    public function testFromArray()
    {
        $this->assertEquals(
                LineString::fromArray([[1,2,3,4], [5,6,7,8]]),
                new LineString([new Point(1,2,3,4), new Point(5,6,7,8)])
        );
    }

    public function testGeometryType()
    {
        $line = new LineString();

        $this->assertEquals(LineString::LINE_STRING, $line->geometryType());

        $this->assertInstanceOf(LineString::class, $line);
        $this->assertInstanceOf(\geoPHP\Geometry\Curve::class, $line);
        $this->assertInstanceOf(\geoPHP\Geometry\Collection::class, $line);
        $this->assertInstanceOf(\geoPHP\Geometry\Geometry::class, $line);
    }

    public function testIsEmpty()
    {
        $line1 = new LineString();
        $this->assertTrue($line1->isEmpty());

        $line2 = new LineString($this->createPoints([[1,2], [3,4]]));
        $this->assertFalse($line2->isEmpty());
    }

    public function testDimension()
    {
        $this->assertSame((new LineString())->dimension(), 1);
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param array $points
     */
    public function testNumPoints($points)
    {
        $line = new LineString($this->createPoints($points));
        $this->assertCount($line->numPoints(), $points);
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param array $points
     */    
    public function testPointN($points)
    {
        $components = $this->createPoints($points);
        $line = new LineString($components);

        $this->assertNull($line->pointN(0));

        for ($i=1; $i < count($components); $i++) {
            // positive n
            $this->assertEquals($components[$i-1], $line->pointN($i));

            // negative n
            $this->assertEquals($components[count($components)-$i], $line->pointN(-$i));
        }
    }

    public function providerCentroid()
    {
        return [
            'empty LineString' => [[], new Point()],
            'null coordinates' => [[[0, 0], [0, 0]], new Point(0, 0)],
            '↗ vector' => [[[0, 0], [1, 1]], new Point(0.5, 0.5)],
            '↙ vector' => [[[0, 0], [-1, -1]], new Point(-0.5, -0.5)],
            'random geographical coordinates' => [[
                    [20.0390625, -16.97274101999901],
                    [-11.953125, 17.308687886770034],
                    [0.703125, 52.696361078274485],
                    [30.585937499999996, 52.696361078274485],
                    [42.5390625, 41.77131167976407],
                    [-13.359375, 38.8225909761771],
                    [18.984375, 17.644022027872726]
            ], new Point(8.717980875505775, 31.130453138673776)],
            'crossing the antimeridian' => [[[170, 47], [-170, 47]], new Point(0, 47)]
        ];
    }

    /**
     * @dataProvider providerCentroid
     *
     * @param array $points
     * @param Point $centroidPoint
     */
    public function testCentroid($points, $centroidPoint)
    {
        $line = LineString::fromArray($points);
        $centroid = $line->centroid();
        $centroid->setGeos(null);

        $this->assertEquals($centroidPoint, $centroid);
    }

    public function providerIsSimple()
    {
        return [
                'simple' =>
                    [[[0, 0], [0, 10]], true],
                'self-crossing' =>
                    [[[0, 0], [10, 0], [10, 10], [0, -10]], false],
//                'self-tangent' =>
//                    [[[0, 0], [10, 0], [-10, 0]], false],
            // FIXME: isSimple() fails to check self-tangency
        ];
    }

    /**
     * @dataProvider providerIsSimple
     *
     * @param array $points
     * @param bool  $result
     */
    public function testIsSimple($points, $result)
    {
        $line = LineString::fromArray($points);

        $this->assertSame($line->isSimple(), $result);
    }

    public function providerLength() {
        return [
                [[[0, 0], [10, 0]], 10.0],
                [[[1, 1], [2, 2], [2, 3.5], [1, 3], [1, 2], [2, 1]], 6.446461113496085],
        ];
    }

    /**
     * @dataProvider providerLength
     *
     * @param array $points
     * @param float $result
     */
    public function testLength($points, $result)
    {
        $line = LineString::fromArray($points);

        $this->assertSame($line->length(), $result);
    }

    public function providerLength3D() {
        return [
                [[[0, 0, 0], [10, 0, 10]], 14.142135623730951],
                [[[1, 1, 0], [2, 2, 2], [2, 3.5, 0], [1, 3, 2], [1, 2, 0], [2, 1, 2]], 11.926335310544065],
        ];
    }

    /**
     * @dataProvider providerLength3D
     *
     * @param array $points
     * @param float $result
     */
    public function testLength3D($points, $result)
    {
        $line = LineString::fromArray($points);

        $this->assertSame($line->length3D(), $result);
    }

    public function providerLengths() {
        return [
                [[[0, 0], [0, 0]], [
                        'greatCircle' => 0.0,
                        'haversine'   => 0.0,
                        'vincenty'    => 0.0,
                        'PostGIS'     => 0.0
                ]],
                [[[0, 0], [10, 0]], [
                        'greatCircle' => 1113194.9079327357,
                        'haversine'   => 1113194.9079327371,
                        'vincenty'    => 1113194.9079322326,
                        'PostGIS'     => 1113194.90793274
                ]],
                [[[0, 0, 0], [10, 0, 5000]], [
                        'greatCircle' => 1113206.136817154,
                        'haversine'   => 1113194.9079327371,
                        'vincenty'    => 1113194.9079322326,
                        'PostGIS'     => 1113194.90793274
                ]],
                [[[0, 47], [10, 47]], [
                        'greatCircle' => 758681.06593496865,
                        'haversine'   => 758681.06593497901,
                        'vincenty'    => 760043.0186457854,
                        'postGIS'     => 760043.018642104
                ]],
                [[[1, 1, 0], [2, 2, 2], [2, 3.5, 0], [1, 3, 2], [1, 2, 0], [2, 1, 2]], [
                        'greatCircle' => 717400.38999229996,
                        'haversine'   => 717400.38992081373,
                        'vincenty'    => 714328.06433538091,
                        'postGIS'     => 714328.064406871
                ]],
                [[[19, 47], [19.000001, 47], [19.000001, 47.000001], [19.000001, 47.000002], [19.000002, 47.000002]], [
                        'greatCircle' => 0.37447839912084818,
                        'haversine'   => 0.36386002147417207,
                        'vincenty'    => 0.37445330532190713,
                        'postGIS'     => 0.374453678675281
                ]]
        ];
    }

    /**
     * @dataProvider providerLengths
     *
     * @param array $points
     * @param array $results
     */
    public function testGreatCircleLength($points, $results)
    {
        $line = LineString::fromArray($points);

        $this->assertEquals($line->greatCircleLength(), $results['greatCircle'], '', 1e-8);
    }

    /**
     * @dataProvider providerLengths
     *
     * @param array $points
     * @param array $results
     */
    public function testHaversineLength($points, $results)
    {
        $line = LineString::fromArray($points);

        $this->assertEquals($line->haversineLength(), $results['haversine'], '', 1e-7);
    }

    /**
     * @dataProvider providerLengths
     *
     * @param array $points
     * @param array $results
     */
    public function testVincentyLength($points, $results)
    {
        $line = LineString::fromArray($points);

        $this->assertEquals($line->vincentyLength(), $results['vincenty'], '', 1e-8);
    }

    public function testVincentyLengthAntipodalPoints()
    {
        $line = LineString::fromArray([[-89.7, 0], [89.7, 0]]);

        $this->assertNull($line->vincentyLength());
    }

    public function testExplode()
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

    public function providerDistance()
    {
        return [
            'Point on vertex' =>
                [new Point(0, 10), 0.0],
            'Point, closest distance is 10' =>
                [new Point(10, 10), 10.0],
            'LineString, same points' =>
                [LineString::fromArray([[0, 10], [10, 10]]), 0.0],
            'LineString, closest distance is 10' =>
                [LineString::fromArray([[10, 10], [20, 20]]), 10.0],
            'intersecting line' =>
                [LineString::fromArray([[-10, 5], [10, 5]]), 0.0],
            'GeometryCollection' =>
                [new \geoPHP\Geometry\GeometryCollection([LineString::fromArray([[10, 10], [20, 20]])]), 10.0],
            // TODO: test other types
        ];
    }

    /**
     * @dataProvider providerDistance
     *
     * @param Geometry $otherGeometry
     * @param float $expectedDistance
     */
    public function testDistance($otherGeometry, $expectedDistance)
    {
        $line = LineString::fromArray([[0, 0], [0, 10]]);

        $this->assertSame($line->distance($otherGeometry), $expectedDistance);
    }

    public function testMinimumAndMaximumZAndMAndDifference()
    {
        $line = LineString::fromArray([[0, 0, 100.0, 0.0], [1, 1, 50.0, -0.5], [2, 2, 150.0, -1.0], [3, 3, 75.0, 0.5]]);

        $this->assertSame($line->minimumZ(), 50.0);
        $this->assertSame($line->maximumZ(), 150.0);

        $this->assertSame($line->minimumM(), -1.0);
        $this->assertSame($line->maximumM(), 0.5);

        $this->assertSame($line->zDifference(), 25.0);
        $this->assertSame(LineString::fromArray([[0, 1], [2, 3]])->zDifference(), null);
    }

    /**
     * @return array[] [tolerance, gain, loss]
     */
    public function providerElevationGainAndLossByTolerance()
    {
        return [
                [null, 50.0, 30.0],
                [0, 50.0, 30.0],
                [5, 48.0, 28.0],
                [15, 36.0, 16.0]
        ];
    }

    /**
     * @dataProvider providerElevationGainAndLossByTolerance
     *
     * @param float|null $tolerance
     * @param float $gain
     * @param float $loss
     */
    public function testElevationGainAndLoss($tolerance, $gain, $loss)
    {
        $line = LineString::fromArray(
                [[0, 0, 100], [0, 0, 102], [0, 0, 105], [0, 0, 103], [0, 0, 110], [0, 0, 118],
                [0, 0, 102], [0, 0, 108], [0, 0, 102], [0, 0, 108], [0, 0, 102], [0, 0, 120] ]
        );

        $this->assertSame($line->elevationGain($tolerance), $gain);

        $this->assertSame($line->elevationLoss($tolerance), $loss);
    }
}