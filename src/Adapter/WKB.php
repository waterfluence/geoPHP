<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\MultiPoint;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\MultiLineString;
use geoPHP\Geometry\Polygon;
use geoPHP\Geometry\MultiPolygon;

/*
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/WKB encoder/decoder
 * Reader can decode EWKB too. Writer always encodes valid WKBs
 *
 */
class WKB implements GeoAdapter
{
    const Z_MASK = 0x80000000;
    const M_MASK = 0x40000000;
    const SRID_MASK = 0x20000000;
    const WKB_XDR = 1;
    const WKB_NDR = 0;

    protected $hasZ = false;

    protected $hasM = false;

    protected $hasSRID = false;

    protected $SRID;

    protected $dimension = 2;

    /** @var  BinaryReader $reader */
    protected $reader;

    /** @var  BinaryWriter $writer */
    protected $writer;

    /** @var array Maps Geometry types to WKB type codes */
    public static $typeMap = [
            Geometry::POINT               => 1,
            Geometry::LINE_STRING         => 2,
            Geometry::POLYGON             => 3,
            Geometry::MULTI_POINT         => 4,
            Geometry::MULTI_LINE_STRING   => 5,
            Geometry::MULTI_POLYGON       => 6,
            Geometry::GEOMETRY_COLLECTION => 7,
            //Not supported types:
            Geometry::CIRCULAR_STRING     => 8,
            Geometry::COMPOUND_CURVE      => 9,
            Geometry::CURVE_POLYGON       => 10,
            Geometry::MULTI_CURVE         => 11,
            Geometry::MULTI_SURFACE       => 12,
            Geometry::CURVE               => 13,
            Geometry::SURFACE             => 14,
            Geometry::POLYHEDRAL_SURFACE  => 15,
            Geometry::TIN                 => 16,
            Geometry::TRIANGLE            => 17,
    ];

    /**
     * Read WKB into geometry objects
     *
     * @param string $wkb         Well-known-binary string
     * @param bool   $isHexString If this is a hexadecimal string that is in need of packing
     *
     * @return Geometry
     *
     * @throws \Exception
     */
    public function read($wkb, $isHexString = false): \geoPHP\Geometry\GeometryCollection|\geoPHP\Geometry\LineString|\geoPHP\Geometry\MultiLineString|\geoPHP\Geometry\MultiPoint|\geoPHP\Geometry\MultiPolygon|\geoPHP\Geometry\Point|\geoPHP\Geometry\Polygon|null
    {
        if ($isHexString) {
            $wkb = pack('H*', $wkb);
        }

        if (empty($wkb)) {
            throw new \Exception('Cannot read empty WKB geometry. Found ' . gettype($wkb));
        }

        $this->reader = new BinaryReader($wkb);

        $geometry = $this->getGeometry();

        $this->reader->close();

        return $geometry;
    }

    /**
     * @return Geometry
     * @throws \Exception
     */
    protected function getGeometry(): \geoPHP\Geometry\Point|\geoPHP\Geometry\LineString|\geoPHP\Geometry\Polygon|\geoPHP\Geometry\GeometryCollection|\geoPHP\Geometry\MultiLineString|\geoPHP\Geometry\MultiPoint|\geoPHP\Geometry\MultiPolygon|null
    {
        $this->hasZ = false;
        $this->hasM = false;
        $SRID = null;

        $this->reader->setEndianness(
            $this->reader->readSInt8() === self::WKB_XDR ? BinaryReader::LITTLE_ENDIAN : BinaryReader::BIG_ENDIAN
        );

        $wkbType = $this->reader->readUInt32();

        if (($wkbType & $this::SRID_MASK) === $this::SRID_MASK) {
            $SRID = $this->reader->readUInt32();
        }
        $geometryType = null;
        if ($wkbType >= 1000 && $wkbType < 2000) {
            $this->hasZ = true;
            $geometryType = $wkbType - 1000;
        } elseif ($wkbType >= 2000 && $wkbType < 3000) {
            $this->hasM = true;
            $geometryType = $wkbType - 2000;
        } elseif ($wkbType >= 3000 && $wkbType < 4000) {
            $this->hasZ = true;
            $this->hasM = true;
            $geometryType = $wkbType - 3000;
        }

        if (($wkbType & $this::Z_MASK) !== 0) {
            $this->hasZ = true;
        }
        if (($wkbType & $this::M_MASK) !== 0) {
            $this->hasM = true;
        }
        $this->dimension = 2 + ($this->hasZ ? 1 : 0) + ($this->hasM ? 1 : 0);

        if (!$geometryType) {
            $geometryType = $wkbType & 0xF; // remove any masks from type
        }
        $geometry = match ($geometryType) {
            1 => $this->getPoint(),
            2 => $this->getLineString(),
            3 => $this->getPolygon(),
            4 => $this->getMulti('Point'),
            5 => $this->getMulti('LineString'),
            6 => $this->getMulti('Polygon'),
            7 => $this->getMulti('Geometry'),
            default => throw new \Exception(
                'Geometry type ' . $geometryType .
                ' (' . (array_search($geometryType, self::$typeMap) ?: 'unknown') . ') not supported'
            ),
        };
        if ($geometry && $SRID) {
            $geometry->setSRID($SRID);
        }
        return $geometry;
    }

    protected function getPoint(): ?\geoPHP\Geometry\Point
    {
        $coordinates = $this->reader->readDoubles($this->dimension * 8);
        $point = null;
        switch (count($coordinates)) {
            case 2:
                $point = new Point($coordinates[0], $coordinates[1]);
                break;
            case 3:
                if ($this->hasZ) {
                    $point = new Point($coordinates[0], $coordinates[1], $coordinates[2]);
                } else {
                    $point = new Point($coordinates[0], $coordinates[1], null, $coordinates[2]);
                }
                break;
            case 4:
                $point = new Point($coordinates[0], $coordinates[1], $coordinates[2], $coordinates[3]);
                break;
        }
        return $point;
    }

    protected function getLineString(): \geoPHP\Geometry\LineString
    {
        // Get the number of points expected in this string out of the first 4 bytes
        $lineLength = $this->reader->readUInt32();

        // Return an empty linestring if there is no line-length
        if (!$lineLength) {
            return new LineString();
        }

        $components = [];
        for ($i = 0; $i < $lineLength; ++$i) {
            $point = $this->getPoint();
            if ($point instanceof \geoPHP\Geometry\Point) {
                $components[] = $point;
            }
        }
        return new LineString($components);
    }

    protected function getPolygon(): \geoPHP\Geometry\Polygon
    {
        // Get the number of linestring expected in this poly out of the first 4 bytes
        $polyLength = $this->reader->readUInt32();

        $components = [];
        $i = 1;
        while ($i <= $polyLength) {
            $ring = $this->getLineString();
            if (!$ring->isEmpty()) {
                $components[] = $ring;
            }
            $i++;
        }

        return new Polygon($components);
    }

    protected function getMulti($type): \geoPHP\Geometry\MultiPoint|\geoPHP\Geometry\MultiLineString|\geoPHP\Geometry\MultiPolygon|\geoPHP\Geometry\GeometryCollection|null
    {
        // Get the number of items expected in this multi out of the first 4 bytes
        $multiLength = $this->reader->readUInt32();

        $components = [];
        for ($i = 0; $i < $multiLength; $i++) {
            $component = $this->getGeometry();
            $component->setSRID(null);
            $components[] = $component;
        }
        return match ($type) {
            'Point' => new MultiPoint($components),
            'LineString' => new MultiLineString($components),
            'Polygon' => new MultiPolygon($components),
            'Geometry' => new GeometryCollection($components),
            default => null,
        };
    }

    /**
     * Serialize geometries into WKB string.
     *
     * @param Geometry $geometry The geometry
     * @param boolean $writeAsHex Write the result in binary or hexadecimal system
     * @param boolean $bigEndian Write in BigEndian or LittleEndian byte order
     *
     * @return string The WKB string representation of the input geometries
     */
    public function write(Geometry $geometry, $writeAsHex = false, $bigEndian = false)
    {

        $this->writer = new BinaryWriter($bigEndian ? BinaryWriter::BIG_ENDIAN : BinaryWriter::LITTLE_ENDIAN);

        $wkb = $this->writeGeometry($geometry);

        return $writeAsHex ? current(unpack('H*', $wkb)) : $wkb;
    }

    /**
     * @param Geometry $geometry
     */
    protected function writeGeometry($geometry): string
    {
        $this->hasZ = $geometry->hasZ();
        $this->hasM = $geometry->isMeasured();

        $wkb = $this->writer->writeSInt8($this->writer->isBigEndian() ? self::WKB_NDR : self::WKB_XDR);
        $wkb .= $this->writeType($geometry);
        match ($geometry->geometryType()) {
            /** @var Point $geometry */
            Geometry::POINT => $wkb .= $this->writePoint($geometry),
            /** @var LineString $geometry */
            Geometry::LINE_STRING => $wkb .= $this->writeLineString($geometry),
            /** @var Polygon $geometry */
            Geometry::POLYGON => $wkb .= $this->writePolygon($geometry),
            /** @var MultiPoint $geometry */
            Geometry::MULTI_POINT => $wkb .= $this->writeMulti($geometry),
            /** @var MultiLineString $geometry */
            Geometry::MULTI_LINE_STRING => $wkb .= $this->writeMulti($geometry),
            /** @var MultiPolygon $geometry */
            Geometry::MULTI_POLYGON => $wkb .= $this->writeMulti($geometry),
            /** @var GeometryCollection $geometry */
            Geometry::GEOMETRY_COLLECTION => $wkb .= $this->writeMulti($geometry),
            default => $wkb,
        };
        return $wkb;
    }

    /**
     * @param Point $point
     */
    protected function writePoint($point): string
    {
        if ($point->isEmpty()) {
            return $this->writer->writeDouble(NAN) . $this->writer->writeDouble(NAN);
        }
        $wkb = $this->writer->writeDouble($point->x()) . $this->writer->writeDouble($point->y());

        if ($this->hasZ) {
            $wkb .= $this->writer->writeDouble($point->z());
        }
        if ($this->hasM) {
            $wkb .= $this->writer->writeDouble($point->m());
        }
        return $wkb;
    }

    /**
     * @param LineString $line
     * @return string
     */
    protected function writeLineString($line)
    {
        // Set the number of points in this line
        $wkb = $this->writer->writeUInt32($line->numPoints());

        // Set the coords
        foreach ($line->getComponents() as $point) {
            $wkb .= $this->writePoint($point);
        }

        return $wkb;
    }

    /**
     * @param Polygon $poly
     * @return string
     */
    protected function writePolygon($poly)
    {
        // Set the number of lines in this poly
        $wkb = $this->writer->writeUInt32($poly->numGeometries());

        // Write the lines
        foreach ($poly->getComponents() as $line) {
            $wkb .= $this->writeLineString($line);
        }

        return $wkb;
    }

    /**
     * @param MultiPoint|MultiPolygon|MultiLineString|GeometryCollection $geometry
     * @return string
     */
    protected function writeMulti($geometry)
    {
        // Set the number of components
        $wkb = $this->writer->writeUInt32($geometry->numGeometries());

        // Write the components
        foreach ($geometry->getComponents() as $component) {
            $wkb .= $this->writeGeometry($component);
        }

        return $wkb;
    }

    /**
     * @param Geometry $geometry
     * @param bool $writeSRID
     */
    protected function writeType($geometry, $writeSRID = false): string
    {
        $type = self::$typeMap[$geometry->geometryType()];
        // Binary OR to mix in additional properties
        if ($this->hasZ) {
            $type |= $this::Z_MASK;
        }
        if ($this->hasM) {
            $type |= $this::M_MASK;
        }
        if ($geometry->SRID() && $writeSRID) {
            $type |= $this::SRID_MASK;
        }
        return $this->writer->writeUInt32($type) .
            ($geometry->SRID() && $writeSRID ? $this->writer->writeUInt32($this->SRID) : '');
    }
}
