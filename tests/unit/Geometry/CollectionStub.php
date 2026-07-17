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
    public function dimension(): null
    {
        return null;
    }

    public function geometryType(): string
    {
        return 'CollectionStub';
    }

    public function isSimple(): null
    {
        return null;
    }

    public function boundary(): null
    {
        return null;
    }

    public function area(): null
    {
        return null;
    }

    public function centroid(): null
    {
        return null;
    }

    public function length(): null
    {
        return null;
    }

    public function length3D(): null
    {
        return null;
    }

    public function startPoint(): null
    {
        return null;
    }

    public function endPoint(): null
    {
        return null;
    }

    public function isRing(): null
    {
        return null;
    }

    public function isClosed(): null
    {
        return null;
    }

    public function pointN($n): null
    {
        return null;
    }

    public function exteriorRing(): null
    {
        return null;
    }

    public function numInteriorRings(): null
    {
        return null;
    }

    public function interiorRingN($n): null
    {
        return null;
    }

    public function greatCircleLength($radius = null): null
    {
        return null;
    }

    public function haversineLength(): null
    {
        return null;
    }

    public function minimumZ(): null
    {
        return null;
    }

    public function maximumZ(): null
    {
        return null;
    }

    public function minimumM(): null
    {
        return null;
    }

    public function maximumM(): null
    {
        return null;
    }

    public function zDifference(): null
    {
        return null;
    }

    public function elevationGain($verticalTolerance = 0): null
    {
        return null;
    }

    public function elevationLoss($verticalTolerance = 0): null
    {
        return null;
    }
}
