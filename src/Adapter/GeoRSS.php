<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Collection;
use geoPHP\geoPHP;
use geoPHP\Geometry\Geometry;
use geoPHP\Geometry\GeometryCollection;
use geoPHP\Geometry\Point;
use geoPHP\Geometry\LineString;
use geoPHP\Geometry\Polygon;

/*
 * Copyright (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/GeoRSS encoder/decoder
 */
class GeoRSS implements GeoAdapter
{
    /**
     * @var \DOMDocument $xmlObject
     */
    protected $xmlObject;

    private string $nss = ''; // Name-space string. eg 'georss:'

    /**
     * Read GeoRSS string into geometry objects
     *
     * @param string $georss - an XML feed containing geoRSS
     *
     * @return Geometry|GeometryCollection
     */
    public function read($georss)
    {
        return $this->geomFromText($georss);
    }

    /**
     * Serialize geometries into a GeoRSS string.
     *
     * @param boolean|string $namespace
     * @return string The georss string representation of the input geometries
     */
    public function write(Geometry $geometry, $namespace = false): string
    {
        if ($namespace) {
            $this->nss = $namespace . ':';
        }
        return $this->geometryToGeoRSS($geometry) ?: '';
    }

    public function geomFromText($text)
    {
        // Change to lower-case, strip all CDATA, and de-namespace
        $text = strtolower((string) $text);
        $text = preg_replace('/<!\[cdata\[(.*?)\]\]>/s', '', $text);

        // Load into DOMDocument
        $xmlObject = new \DOMDocument();
        @$xmlObject->loadXML($text);
        if ($xmlObject === false) {
            throw new \Exception("Invalid GeoRSS: " . $text);
        }

        $this->xmlObject = $xmlObject;
        try {
            $geom = $this->geomFromXML();
        } catch (\Exception $e) {
            throw new \Exception("Cannot Read Geometry From GeoRSS: " . $e->getMessage(), $e->getCode(), $e);
        }

        return $geom;
    }

    protected function geomFromXML()
    {
        $geometries = [];
        $geometries = array_merge($geometries, $this->parsePoints());
        $geometries = array_merge($geometries, $this->parseLines());
        $geometries = array_merge($geometries, $this->parsePolygons());
        $geometries = array_merge($geometries, $this->parseBoxes());
        $geometries = array_merge($geometries, $this->parseCircles());

        if ($geometries === []) {
            throw new \Exception("Invalid / Empty GeoRSS");
        }

        return geoPHP::geometryReduce($geometries);
    }

    /**
     * @return \geoPHP\Geometry\Point[]
     */
    protected function getPointsFromCoordinates($string): array
    {
        $coordinates = [];
        $latitudeAndLongitude = explode(' ', (string) $string);
        $lat = 0;
        foreach ($latitudeAndLongitude as $key => $item) {
            if ($key % 2 === 0) {
                // It's a latitude
                $lat = is_numeric($item) ? $item : NAN;
            } else {
                // It's a longitude
                $lon = is_numeric($item) ? $item : NAN;
                $coordinates[] = new Point($lon, $lat);
            }
        }
        return $coordinates;
    }

    /**
     * @return mixed[]
     */
    protected function parsePoints(): array
    {
        $points = [];
        $pointElements = $this->xmlObject->getElementsByTagName('point');
        foreach ($pointElements as $pt) {
            $pointArray = $this->getPointsFromCoordinates(trim((string) $pt->firstChild->nodeValue));
            $points[] = $pointArray === [] ? new Point() : $pointArray[0];
        }
        return $points;
    }

    /**
     * @return \geoPHP\Geometry\LineString[]
     */
    protected function parseLines(): array
    {
        $lines = [];
        $lineElements = $this->xmlObject->getElementsByTagName('line');
        foreach ($lineElements as $line) {
            $components = $this->getPointsFromCoordinates(trim((string) $line->firstChild->nodeValue));
            $lines[] = new LineString($components);
        }
        return $lines;
    }

    /**
     * @return \geoPHP\Geometry\Polygon[]
     */
    protected function parsePolygons(): array
    {
        $polygons = [];
        $polygonElements = $this->xmlObject->getElementsByTagName('polygon');
        foreach ($polygonElements as $polygon) {
            /** @noinspection PhpUndefinedMethodInspection */
            if ($polygon->hasChildNodes()) {
                $points = $this->getPointsFromCoordinates(trim((string) $polygon->firstChild->nodeValue));
                $exteriorRing = new LineString($points);
                $polygons[] = new Polygon([$exteriorRing]);
            } else {
                // It's an EMPTY polygon
                $polygons[] = new Polygon();
            }
        }
        return $polygons;
    }

    // Boxes are rendered into polygons
    /**
     * @return \geoPHP\Geometry\Polygon[]
     */
    protected function parseBoxes(): array
    {
        $polygons = [];
        $boxElements = $this->xmlObject->getElementsByTagName('box');
        foreach ($boxElements as $box) {
            $parts = explode(' ', trim((string) $box->firstChild->nodeValue));
            $components = [
                    new Point($parts[3], $parts[2]),
                    new Point($parts[3], $parts[0]),
                    new Point($parts[1], $parts[0]),
                    new Point($parts[1], $parts[2]),
                    new Point($parts[3], $parts[2]),
            ];
            $exteriorRing = new LineString($components);
            $polygons[] = new Polygon([$exteriorRing]);
        }
        return $polygons;
    }

    // Circles are rendered into points
    // @@TODO: Add good support once we have circular-string geometry support
    /**
     * @return \geoPHP\Geometry\Point[]
     */
    protected function parseCircles(): array
    {
        $points = [];
        $circleElements = $this->xmlObject->getElementsByTagName('circle');
        foreach ($circleElements as $circle) {
            $parts = explode(' ', trim((string) $circle->firstChild->nodeValue));
            $points[] = new Point($parts[1], $parts[0]);
        }
        return $points;
    }

    /**
     * @param Geometry $geometry
     */
    protected function geometryToGeoRSS($geometry): ?string
    {
        $type = $geometry->geometryType();
        return match ($type) {
            Geometry::POINT => $this->pointToGeoRSS($geometry),
            /** @noinspection PhpParamsInspection */
            Geometry::LINE_STRING => $this->linestringToGeoRSS($geometry),
            /** @noinspection PhpParamsInspection */
            Geometry::POLYGON => $this->PolygonToGeoRSS($geometry),
            /** @noinspection PhpParamsInspection */
            Geometry::MULTI_POINT, Geometry::MULTI_LINE_STRING, Geometry::MULTI_POLYGON, Geometry::GEOMETRY_COLLECTION => $this->collectionToGeoRSS($geometry),
            default => null,
        };
    }

    /**
     * @param Geometry $geometry
     */
    private function pointToGeoRSS($geometry): string
    {
        return '<' . $this->nss . 'point>' . $geometry->y() . ' ' . $geometry->x() . '</' . $this->nss . 'point>';
    }

    /**
     * @param LineString $geometry
     */
    private function linestringToGeoRSS($geometry): string
    {
        $output = '<' . $this->nss . 'line>';
        foreach ($geometry->getComponents() as $k => $point) {
            $output .= $point->y() . ' ' . $point->x();
            if ($k < ($geometry->numGeometries() - 1)) {
                $output .= ' ';
            }
        }
        return $output . ('</' . $this->nss . 'line>');
    }

    /**
     * @param Polygon $geometry
     */
    private function polygonToGeoRSS($geometry): string
    {
        $output = '<' . $this->nss . 'polygon>';
        $exteriorRing = $geometry->exteriorRing();
        foreach ($exteriorRing->getComponents() as $k => $point) {
            $output .= $point->y() . ' ' . $point->x();
            if ($k < ($exteriorRing->numGeometries() - 1)) {
                $output .= ' ';
            }
        }
        return $output . ('</' . $this->nss . 'polygon>');
    }

    /**
     * @param Collection $geometry
     */
    public function collectionToGeoRSS($geometry): string
    {
        $georss = '<' . $this->nss . 'where>';
        $components = $geometry->getComponents();
        foreach ($components as $component) {
            $georss .= $this->geometryToGeoRSS($component);
        }

        return $georss . ('</' . $this->nss . 'where>');
    }
}
