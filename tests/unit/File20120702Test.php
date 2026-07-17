<?php

declare(strict_types=1);

namespace geoPHP\Tests;

use \geoPHP\geoPHP;
use \geoPHP\Geometry\Geometry;
use PHPUnit\Framework\TestCase;

// FIXME file 20120702.gpx contains one MultiLineString but _method_tester() also wants to test Points and LineStrings (ie does nothing)

final class File20120702Test extends TestCase
{

  function testMethods(): void
  {
    $format = 'gpx';
    $value = file_get_contents('tests/input/20120702.gpx');
    $geometry = geoPHP::load($value, $format);

    $methods = [
      ['name' => 'area'],
      ['name' => 'boundary'],
      ['name' => 'getBBox'],
      ['name' => 'centroid'],
      ['name' => 'length'],
      ['name' => 'greatCircleLength', 'argument' => 6378137],
      ['name' => 'haversineLength'],
      ['name' => 'y'],
      ['name' => 'x'],
      ['name' => 'numGeometries'],
      ['name' => 'geometryN', 'argument' => '1'],
      ['name' => 'startPoint'],
      ['name' => 'endPoint'],
      ['name' => 'isRing'],
      ['name' => 'isClosed'],
      ['name' => 'numPoints'],
      ['name' => 'pointN', 'argument' => '1'],
      ['name' => 'exteriorRing'],
      ['name' => 'numInteriorRings'],
      ['name' => 'interiorRingN', 'argument' => '1'],
      ['name' => 'dimension'],
      ['name' => 'geometryType'],
      ['name' => 'SRID'],
      ['name' => 'setSRID', 'argument' => '4326'],
    ];

    foreach($methods as $method) {
      $argument = NULL;
      $method_name = $method['name'];
      if (isset($method['argument'])) {
        $argument = $method['argument'];
      }
      $this->_methods_tester($geometry, $method_name, $argument);
    }
  }

  /**
   * @param Geometry $geometry
   * @param string $method_name
   * @param mixed $argument
   */
  function _methods_tester($geometry, $method_name, $argument): void {

    if (!method_exists($geometry, $method_name)) {
      $this->fail("Method ".$method_name.'() doesn\'t exists.');
      return;
    }

    $failedOnMessage = $geometry->geometryType() . ' failed on ' . $method_name ;

    switch ($method_name) {
      case 'y':
      case 'x':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'geometryN':
      case 'isClosed':
      case 'numGeometries':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'startPoint':
      case 'endPoint':
      case 'isRing':
      case 'pointN':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'exteriorRing':
      case 'numInteriorRings':
      case 'interiorRingN':
      case 'SRID':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'setSRID':
        //TODO: The method setSRID() should return TRUE.
        break;
      case 'getBBox':
      case 'centroid':
      case 'boundary':
        if ($geometry->geometryType() == 'Point') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'length':
        if ($geometry->geometryType() == 'Point') {
          $this->assertEquals(0, $geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertEquals($geometry->$method_name($argument), (float) '0.11624637315233', $failedOnMessage);
        }
        break;
      case 'numPoints':
        if ($geometry->geometryType() == 'Point') {
          $this->assertEquals(1, $geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'dimension':
        if ($geometry->geometryType() == 'Point') {
          $this->assertEquals(0, $geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertEquals(1, $geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertEquals(1, $geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'greatCircleLength':
        if ($geometry->geometryType() == 'Point') {
          $this->assertEquals(0, $geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'LineString') {
          $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        }
        if ($geometry->geometryType() == 'MultiLineString') {
          $this->assertNotEquals('9500.9359867418', $geometry->$method_name($argument), $failedOnMessage);
        }
        break;
      case 'haversineLength':
      case 'area':
      case 'geometryType':
        $this->assertNotNull($geometry->$method_name($argument), $failedOnMessage);
        break;
      default:
        $this->assertTrue($geometry->$method_name($argument), $failedOnMessage);
    }
  }
}
