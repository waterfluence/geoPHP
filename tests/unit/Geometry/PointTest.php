<?php

declare(strict_types=1);

namespace geoPHP\Tests\Geometry;

use geoPHP\Exception\InvalidGeometryException;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of Point geometry
 *
 *
 */
#[\PHPUnit\Framework\Attributes\Group('geometry')]
final class PointTest extends TestCase
{

    public static function providerValidCoordinatesXY(): \Iterator
    {
        yield 'null coordinates' => [0, 0];
        yield 'positive integer' => [10, 20];
        yield 'negative integer' => [-10, -20];
        yield 'WGS84' => [47.1234056789, 19.9876054321];
        yield 'HD72/EOV' => [238084.12, 649977.59];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidCoordinatesXY')]
    public function testValidCoordinatesXY(int|float $x, int|float $y): void
    {
        $point = new Point($x, $y);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());

//        $this->assertIsFloat($point->x());
//        $this->assertIsFloat($point->y());
        $this->assertTrue(is_float($point->x()));
        $this->assertTrue(is_float($point->y()));
    }

    public static function providerValidCoordinatesXYZ_or_XYM(): \Iterator
    {
        yield 'null coordinates' => [0, 0, 0];
        yield 'positive integer' => [10, 20, 30];
        yield 'negative integer' => [-10, -20, -30];
        yield 'WGS84' => [47.1234056789, 19.9876054321, 100.1];
        yield 'HD72/EOV' => [238084.12, 649977.59, 56.38];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidCoordinatesXYZ_or_XYM')]
    public function testValidCoordinatesXYZ(int|float $x, int|float $y, int|float $z): void
    {
        $point = new Point($x, $y, $z);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertEquals($z, $point->z());
        $this->assertNull($point->m());

//        $this->assertIsFloat($point->x());
//        $this->assertIsFloat($point->y());
//        $this->assertIsFloat($point->z());
        $this->assertTrue(is_float($point->x()));
        $this->assertTrue(is_float($point->y()));
        $this->assertTrue(is_float($point->z()));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidCoordinatesXYZ_or_XYM')]
    function testValidCoordinatesXYM(int|float $x, int|float $y, int|float $m): void
    {
        $point = new Point($x, $y, null, $m);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertEquals($m, $point->m());
        $this->assertNull($point->z());

//        $this->assertIsFloat($point->x());
//        $this->assertIsFloat($point->y());
//        $this->assertIsFloat($point->m());
        $this->assertTrue(is_float($point->x()));
        $this->assertTrue(is_float($point->y()));
        $this->assertTrue(is_float($point->m()));
    }

    public static function providerValidCoordinatesXYZM(): \Iterator
    {
        yield 'null coordinates' => [0, 0, 0, 0];
        yield 'positive integer' => [10, 20, 30, 40];
        yield 'negative integer' => [-10, -20, -30, -40];
        yield 'WGS84' => [47.1234056789, 19.9876054321, 100.1, 0.00001];
        yield 'HD72/EOV' => [238084.12, 649977.59, 56.38, -0.00001];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidCoordinatesXYZM')]
    public function testValidCoordinatesXYZM(int|float $x, int|float $y, int|float $z, int|float $m): void
    {
        $point = new Point($x, $y, $z, $m);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertEquals($z, $point->z());
        $this->assertEquals($m, $point->m());

//        $this->assertIsFloat($point->x());
//        $this->assertIsFloat($point->y());
//        $this->assertIsFloat($point->z());
//        $this->assertIsFloat($point->m());
        $this->assertTrue(is_float($point->x()));
        $this->assertTrue(is_float($point->y()));
        $this->assertTrue(is_float($point->z()));
        $this->assertTrue(is_float($point->m()));
    }

    public function testConstructorWithoutParameters(): void
    {
        $point = new Point();

        $this->assertTrue($point->isEmpty());

        $this->assertNull($point->x());
        $this->assertNull($point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
    }

    public static function providerEmpty(): \Iterator
    {
        yield 'no coordinates' => [];
        yield 'x is null' => [null, 20];
        yield 'y is null' => [10, null];
        yield 'x and y is null' => [null, null, 30];
        yield 'x, y, z is null' => [null, null, null, 40];
        yield 'x, y, z, m is null' => [null, null, null, null];
    }

    /**
     *
     * @param int|float|null $x
     * @param int|float|null $y
     * @param int|float|null $z
     * @param int|float|null $m
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerEmpty')]
    public function testEmpty(?int $x = null, ?int $y = null, ?int $z = null, ?int $m = null): void
    {
        $point = new Point($x, $y, $z, $m);

        $this->assertTrue($point->isEmpty());

        $this->assertNull($point->x());
        $this->assertNull($point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
    }

    public static function providerInvalidCoordinates(): \Iterator
    {
        yield 'string coordinates' => ['x', 'y'];
        yield 'boolean coordinates' => [true, false];
        yield 'z is string' => [1, 2, 'z'];
        yield 'm is string' => [1, 2, 3, 'm'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerInvalidCoordinates')]
    public function testConstructorWithInvalidCoordinates(string|bool|int $x, string|bool|int $y, string|int $z = null, string $m = null): void
    {
        $this->expectException(InvalidGeometryException::class);

        new Point($x, $y, $z, $m);
    }

    public function testGeometryType(): void
    {
        $point = new Point();

        $this->assertSame(\geoPHP\Geometry\Geometry::POINT, $point->geometryType());

        $this->assertInstanceOf(Point::class, $point);
        $this->assertInstanceOf(\geoPHP\Geometry\Geometry::class, $point);
    }

    public static function providerIs3D(): \Iterator
    {
        yield '2 coordinates is not 3D' => [false, 1, 2];
        yield '3 coordinates' => [true, 1, 2, 3];
        yield '4 coordinates' => [true, 1, 2, 3, 4];
        yield 'x, y is null but z is not' => [true, null, null, 3, 4];
        yield 'z is null' => [false, 1, 2, null, 4];
        yield 'empty point' => [false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerIs3D')]
    public function testIs3D(bool $result, ?int $x = null, ?int $y = null, ?int $z = null, int $m = null): void
    {
        $this->assertSame($result, (new Point($x, $y, $z, $m))->is3D());
    }

    public static function providerIsMeasured(): \Iterator
    {
        yield '2 coordinates is false' => [false, 1, 2];
        yield '3 coordinates is false' => [false, 1, 2, 3];
        yield '4 coordinates' => [true, 1, 2, 3, 4];
        yield 'x, y is null but m is not' => [true, null, null, 3, 4];
        yield 'm is null' => [false, 1, 2, 3, null];
        yield 'empty point' => [false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerIsMeasured')]
    public function testIsMeasured(bool $result, ?int $x = null, ?int $y = null, int $z = null, ?int $m = null): void
    {
        $this->assertSame($result, (new Point($x, $y, $z, $m))->isMeasured());
    }

    public function testGetComponents(): void
    {
        $point = new Point(1, 2);
        $components = $point->getComponents();

        //$this->assertIsArray($components);
        $this->assertTrue(is_array($components) );
        $this->assertCount(1, $components);
        $this->assertSame($point, $components[0]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidCoordinatesXYZM')]
    public function testInvertXY(int|float $x, int|float $y, int|float $z, int|float $m): void
    {
        $point = new Point($x, $y, $z, $m);
        $originalPoint = clone $point;
        $point->invertXY();

        $this->assertEquals($x, $point->y());
        $this->assertEquals($y, $point->x());
        $this->assertEquals($z, $point->z());
        $this->assertEquals($m, $point->m());

        $point->invertXY();
        $this->assertEquals($point, $originalPoint);
    }

    public function testCentroidIsThePointItself(): void
    {
        $point = new Point(1, 2, 3, 4);
        $this->assertSame($point, $point->centroid());
    }

    public function testBBox(): void
    {
        $point = new Point(1, 2);
        $this->assertSame($point->getBBox(), [
                'maxy' => 2.0,
                'miny' => 2.0,
                'maxx' => 1.0,
                'minx' => 1.0,
        ]);
    }

    public function testAsArray(): void
    {
        $pointAsArray = (new Point())->asArray();
        $this->assertCount(2, $pointAsArray);
        $this->assertNan($pointAsArray[0]);
        $this->assertNan($pointAsArray[1]);

        $pointAsArray = (new Point(1, 2))->asArray();
        $this->assertSame($pointAsArray, [1.0, 2.0]);

        $pointAsArray = (new Point(1, 2, 3))->asArray();
        $this->assertSame($pointAsArray, [1.0, 2.0, 3.0]);

        $pointAsArray = (new Point(1, 2, null, 3))->asArray();
        $this->assertSame($pointAsArray, [1.0, 2.0, null, 3.0]);

        $pointAsArray = (new Point(1, 2, 3, 4))->asArray();
        $this->assertSame($pointAsArray, [1.0, 2.0, 3.0, 4.0]);
    }

    public function testBoundary(): void
    {
        $this->assertEquals((new Point(1, 2))->boundary(), new GeometryCollection());
    }

    public function testEquals(): void
    {
        $this->assertTrue((new Point())->equals(new Point()));

        $point = new Point(1, 2, 3, 4);
        $this->assertTrue($point->equals(new Point(1, 2, 3, 4)));

        $this->assertTrue($point->equals(new Point(1.0000000001, 2.0000000001, 3, 4)));
        $this->assertTrue($point->equals(new Point(0.9999999999, 1.9999999999, 3, 4)));

        $this->assertFalse($point->equals(new Point(1.000000001, 2.000000001, 3, 4)));
        $this->assertFalse($point->equals(new Point(0.999999999, 1.999999999, 3, 4)));

        $this->assertFalse($point->equals(new GeometryCollection()));
    }

    public function testFlatten(): void
    {
        $point = new Point(1, 2, 3, 4);
        $point->flatten();

        $this->assertEquals(1, $point->x());
        $this->assertEquals(2, $point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
        $this->assertFalse($point->is3D());
        $this->assertFalse($point->isMeasured());
    }

    public static function providerDistance(): \Iterator
    {
        yield 'empty Point' => [new Point(), null];
        yield 'Point x+10' => [new Point(10, 0), 10.0];
        yield 'Point y+10' => [new Point(0, 10), 10.0];
        yield 'Point x+10,y+10' => [new Point(10, 10), 14.142135623730951];
        yield 'LineString, point is a vertex' => [LineString::fromArray([[-10, 10], [0, 0], [10, 10]]), 0.0];
        yield 'LineString, containing a vertex twice' => [LineString::fromArray([[0, 10], [0, 10]]), 10.0];
        yield 'LineString, point on line' => [LineString::fromArray([[-10, -10], [10, 10]]), 0.0];
        yield 'MultiPoint, closest distance is 0' => [MultiPoint::fromArray([[0, 0], [10, 20]]), 0.0];
        yield 'MultiPoint, closest distance is 10' => [MultiPoint::fromArray([[10, 20], [0, 10]]), 10.0];
        yield 'MultiPoint, one of two is empty' => [MultiPoint::fromArray([[], [0, 10]]), 10.0];
        yield 'GeometryCollection, closest component is 10' => [new GeometryCollection([new Point(0,10), new Point()]), 10.0];
    }

    /**
     *
     * @param Geometry $otherGeometry
     * @param float $expectedDistance
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerDistance')]
    public function testDistance(\geoPHP\Geometry\Point|\geoPHP\Geometry\LineString|\geoPHP\Geometry\MultiPoint|\geoPHP\Geometry\GeometryCollection $otherGeometry, ?float $expectedDistance): void
    {
        $point = new Point(0, 0);

        $this->assertSame($point->distance($otherGeometry), $expectedDistance);
    }

    /**
     * @param Geometry $otherGeometry
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerDistance')]
    public function testDistanceEmpty(\geoPHP\Geometry\Point|\geoPHP\Geometry\LineString|\geoPHP\Geometry\MultiPoint|\geoPHP\Geometry\GeometryCollection $otherGeometry): void
    {
        $point = new Point();

        $this->assertNull($point->distance($otherGeometry));
    }

    public function testTrivialMethods(): void
    {
        $point = new Point(1, 2, 3, 4);

        $this->assertSame( 0, $point->dimension() );

        $this->assertSame( 1, $point->numPoints() );

        $this->assertSame( $point->getPoints(), [$point] );

        $this->assertTrue( $point->isSimple());
    }

    public function testMinMaxMethods(): void
    {
        $point = new Point(1, 2, 3, 4);

        $this->assertEquals(3, $point->minimumZ());
        $this->assertEquals(3, $point->maximumZ());
        $this->assertEquals(4, $point->minimumM());
        $this->assertEquals(4, $point->maximumM());
    }

    public static function providerMethodsNotValidForPointReturnsNull(): \Iterator
    {
        yield ['zDifference'];
        yield ['elevationGain'];
        yield ['elevationLoss'];
        yield ['numGeometries'];
        yield ['geometryN'];
        yield ['startPoint'];
        yield ['endPoint'];
        yield ['isRing'];
        yield ['isClosed'];
        yield ['pointN'];
        yield ['exteriorRing'];
        yield ['numInteriorRings'];
        yield ['interiorRingN'];
        yield ['explode'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerMethodsNotValidForPointReturnsNull')]
    public function testPlaceholderMethodsReturnsNull(string $methodName): void
    {
        $this->assertNull( (new Point(1, 2, 3, 4))->$methodName(null) );
    }

    public static function providerMethodsNotValidForPointReturns0(): \Iterator
    {
        yield ['area'];
        yield ['length'];
        yield ['length3D'];
        yield ['greatCircleLength'];
        yield ['haversineLength'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerMethodsNotValidForPointReturns0')]
    public function testPlaceholderMethods(string $methodName): void
    {
        $this->assertEqualsWithDelta(0.0, (new Point(1, 2, 3, 4))->$methodName(null), PHP_FLOAT_EPSILON);
    }

}
