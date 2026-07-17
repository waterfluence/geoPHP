<?php

declare(strict_types=1);

namespace geoPHP\Tests\Adapter;

use geoPHP\geoPHP;
use geoPHP\Geometry\Point;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WKBTest extends TestCase
{
    /**
     * An empty point is encoded in WKB as NaN coordinates (OGC). Reading it
     * back must yield an empty Point, not a Point(NAN, NAN). Otherwise the
     * point reports isEmpty() === false and serializing it coerces NAN to a
     * string, which raises a warning on PHP 8.x.
     */
    #[DataProvider('emptyPointFormats')]
    public function testEmptyPointRoundTrip(string $format): void
    {
        $empty = geoPHP::load('POINT EMPTY', 'wkt');
        self::assertTrue($empty->isEmpty());

        $decoded = geoPHP::load($empty->out($format), $format);

        self::assertInstanceOf(Point::class, $decoded);
        self::assertTrue($decoded->isEmpty(), "$format did not round-trip an empty point");
        self::assertNull($decoded->x());
        self::assertNull($decoded->y());
        self::assertSame('POINT EMPTY', $decoded->out('wkt'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function emptyPointFormats(): array
    {
        return [
            'wkb'  => ['wkb'],
            'ewkb' => ['ewkb'],
        ];
    }
}
