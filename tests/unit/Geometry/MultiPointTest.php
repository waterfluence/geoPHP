<?php

declare(strict_types=1);

namespace geoPHP\Tests\Geometry;

use \geoPHP\Exception\InvalidGeometryException;
use \geoPHP\Geometry\Point;
use \geoPHP\Geometry\MultiPoint;
use \PHPUnit\Framework\TestCase;

/**
 * Unit tests of MultiPoint geometry
 *
 *
 */
#[\PHPUnit\Framework\Attributes\Group('geometry')]
final class MultiPointTest extends TestCase
{

    public static function providerValidComponents(): \Iterator
    {
        yield [[]];
        // no components, empty MultiPoint
        yield [[new Point()]];
        // empty component
        yield [[new Point(1, 2)]];
        yield [[new Point(1, 2), new Point(3, 4)]];
        yield [[new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)]];
    }

    /**
     * @param Point[] $points
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidComponents')]
    public function testValidComponents(array $points): void
    {
        $this->assertNotNull(new MultiPoint($points));
    }

    public static function providerInvalidComponents(): \Iterator
    {
        yield [[\geoPHP\Geometry\LineString::fromArray([[1,2],[3,4]])]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerInvalidComponents')]
    public function testConstructorWithInvalidComponents(array $components): void
    {
        $this->expectException(InvalidGeometryException::class);

        new MultiPoint($components);
    }

    public function testGeometryType(): void
    {
        $multiPoint = new MultiPoint();

        $this->assertSame(\geoPHP\Geometry\Geometry::MULTI_POINT, $multiPoint->geometryType());

        $this->assertInstanceOf('\geoPHP\Geometry\MultiPoint', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\MultiGeometry', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\Geometry', $multiPoint);
    }

    public function testIs3D(): void
    {
        $this->assertTrue( (new Point(1, 2, 3))->is3D() );
        $this->assertTrue( (new Point(1, 2, 3, 4))->is3D() );
        $this->assertTrue( (new Point(null, null, 3, 4))->is3D() );
    }

    public function testIsMeasured(): void
    {
        $this->assertTrue( (new Point(1, 2, null, 4))->isMeasured() );
        $this->assertTrue( (new Point(null, null , null, 4))->isMeasured() );
    }

    public static function providerCentroid(): \Iterator
    {
        yield [[], []];
        yield [[[0, 0], [0, 10]], [0, 5]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerCentroid')]
    public function testCentroid(array $components, array $centroid): void
    {
        $multiPoint = MultiPoint::fromArray($components);

        $this->assertEquals($multiPoint->centroid(), Point::fromArray($centroid));
    }

    public static function providerIsSimple(): \Iterator
    {
        yield [[], true];
        yield [[[0, 0], [0, 10]], true];
        yield [[[1, 1], [2, 2], [1, 3], [1, 2], [2, 1]], true];
        yield [[[0, 10], [0, 10]], false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerIsSimple')]
    public function testIsSimple(array $points, bool $result): void
    {
        $multiPoint = MultiPoint::fromArray($points);

        $this->assertSame($multiPoint->isSimple(), $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerValidComponents')]
    public function testNumPoints(array $points): void
    {
        $multiPoint = new MultiPoint($points);

        $this->assertEquals($multiPoint->numPoints(), $multiPoint->numGeometries());
    }

    public function testTrivialAndNotValidMethods(): void
    {
        $point = new MultiPoint();

        $this->assertSame( 0, $point->dimension() );

        $this->assertEquals( $point->boundary(), new \geoPHP\Geometry\GeometryCollection() );

        $this->assertNull( $point->explode());

        $this->assertTrue( $point->isSimple());
    }

}
