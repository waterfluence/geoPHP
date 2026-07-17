<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Geometry;

/**
 * EWKT (Extended Well Known Text) Adapter
 */
class EWKT extends WKT
{
    /**
     * Serialize geometries into an EWKT string.
     *
     *
     * @return string The Extended-WKT string representation of the input geometries
     */
    public function write(Geometry $geometry): string
    {
        $srid = $geometry->getSRID();
        if ($srid) {
            $wkt = 'SRID=' . $srid . ';';
            return $wkt . $geometry->out('wkt');
        }
        return $geometry->out('wkt');
    }
}
