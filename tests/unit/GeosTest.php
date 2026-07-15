<?php

declare(strict_types=1);

namespace geoPHP\Tests;

use \geoPHP\geoPHP;
use PHPUnit\Framework\TestCase;

final class GeosTest extends TestCase
{

  function testGeos(): void
  {
    if (!geoPHP::geosInstalled()) {
      $this->markTestSkipped('GEOS not installed');
      return;
    }

    foreach (scandir('./input') as $file) {
      $parts = explode('.',$file);
      if ($parts[0] !== '' && $parts[0] !== '0') {
        if ($parts[0] == 'countries_ne_110m') {
          // Due to a bug in GEOS we have to skip some tests
          // It drops TopologyException for valid geometries
          // https://trac.osgeo.org/geos/ticket/737
          continue;
        }

        $format = $parts[1];
        $value = file_get_contents('./input/'.$file);
        echo "\nloading: " . $file . " for format: " . $format;
        $geometry = geoPHP::load($value, $format);

        $geosMethods = [
          ['name' => 'geos'],
          ['name' => 'setGeos', 'argument' => $geometry->geos()],
          ['name' => 'pointOnSurface'],
          ['name' => 'equals', 'argument' => $geometry],
          ['name' => 'equalsExact', 'argument' => $geometry],
          ['name' => 'relate', 'argument' => $geometry],
          ['name' => 'checkValidity'],
          ['name' => 'isSimple'],
          ['name' => 'buffer', 'argument' => '10'],
          ['name' => 'intersection', 'argument' => $geometry],
          ['name' => 'convexHull'],
          ['name' => 'difference', 'argument' => $geometry],
          ['name' => 'symDifference', 'argument' => $geometry],
          ['name' => 'union', 'argument' => $geometry],
          ['name' => 'simplify', 'argument' => '0'],
          ['name' => 'disjoint', 'argument' => $geometry],
          ['name' => 'touches', 'argument' => $geometry],
          ['name' => 'intersects', 'argument' => $geometry],
          ['name' => 'crosses', 'argument' => $geometry],
          ['name' => 'within', 'argument' => $geometry],
          ['name' => 'contains', 'argument' => $geometry],
          ['name' => 'overlaps', 'argument' => $geometry],
          ['name' => 'covers', 'argument' => $geometry],
          ['name' => 'coveredBy', 'argument' => $geometry],
          ['name' => 'distance', 'argument' => $geometry],
          ['name' => 'hausdorffDistance', 'argument' => $geometry],
        ];

        foreach($geosMethods as $method) {
          $argument = NULL;
          $method_name = $method['name'];
          if (isset($method['argument'])) {
            $argument = $method['argument'];
          }
          $error_message = 'Failed on "' . $method_name .'" method with test file "' . $file . '"';
          
          // GEOS don't like empty points
          if ($geometry->geometryType() == 'Point' && $geometry->isEmpty()) {
            continue;
          }

          switch ($method_name) {
            case 'geos':
              $this->assertInstanceOf('GEOSGeometry', $geometry->$method_name($argument), $error_message);
              break;
            case 'equals':
            case 'equalsExact':
            case 'disjoint':
            case 'touches':
            case 'intersects':
            case 'crosses':
            case 'within':
            case 'contains':
            case 'overlaps':
            case 'covers':
            case 'coveredBy':
              $this->assertInternalType('bool', $geometry->$method_name($argument), $error_message);
              break;
            case 'pointOnSurface':
            case 'buffer':
            case 'intersection':
            case 'convexHull':
            case 'difference':
            case 'symDifference':
            case 'union':
            case 'simplify':
              $this->assertInstanceOf('geoPHP\\Geometry\\Geometry', $geometry->$method_name($argument), $error_message);
              break;
            case 'distance':
            case 'hausdorffDistance':
              $this->assertInternalType('double', $geometry->$method_name($argument), $error_message);
              break;
            case 'relate':
              $this->assertRegExp('/[0-9TF]{9}/', $geometry->$method_name($argument), $error_message);
              break;
            case 'checkValidity':
              $this->assertArrayHasKey('valid', $geometry->$method_name($argument), $error_message);
              break;
            case 'isSimple':
              if ($geometry->geometryType() == 'GeometryCollection') {
                $this->assertNull($geometry->$method_name($argument), $error_message);
              } else {
                $this->assertNotNull($geometry->$method_name($argument), $error_message);
              }
              break;
            default:
          }
        }

      }
    }
  }

}
