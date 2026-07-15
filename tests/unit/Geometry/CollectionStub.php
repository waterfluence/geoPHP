<?php

declare(strict_types=1);

namespace geoPHP\Tests\Geometry;

use geoPHP\Geometry\Collection;

/**
 * Concrete test double for the abstract {@see Collection} class.
 *
 * Collection implements most of its behaviour concretely; only the geometry
 * primitives that make no sense on a bare collection remain abstract. This
 * stub supplies trivial implementations so the collection can be instantiated
 * directly in tests (replaces the removed getMockForAbstractClass()).
 */
final class CollectionStub extends Collection
{
    public function dimension()
    {
        return null;
    }

    public function geometryType()
    {
        return 'CollectionStub';
    }

    public function isSimple()
    {
        return null;
    }

    public function boundary()
    {
        return null;
    }

    public function area()
    {
        return null;
    }

    public function centroid()
    {
        return null;
    }

    public function length()
    {
        return null;
    }

    public function length3D()
    {
        return null;
    }

    public function startPoint()
    {
        return null;
    }

    public function endPoint()
    {
        return null;
    }

    public function isRing()
    {
        return null;
    }

    public function isClosed()
    {
        return null;
    }

    public function pointN($n)
    {
        return null;
    }

    public function exteriorRing()
    {
        return null;
    }

    public function numInteriorRings()
    {
        return null;
    }

    public function interiorRingN($n)
    {
        return null;
    }

    public function greatCircleLength($radius = null)
    {
        return null;
    }

    public function haversineLength()
    {
        return null;
    }

    public function minimumZ()
    {
        return null;
    }

    public function maximumZ()
    {
        return null;
    }

    public function minimumM()
    {
        return null;
    }

    public function maximumM()
    {
        return null;
    }

    public function zDifference()
    {
        return null;
    }

    public function elevationGain($verticalTolerance = 0)
    {
        return null;
    }

    public function elevationLoss($verticalTolerance = 0)
    {
        return null;
    }
}
