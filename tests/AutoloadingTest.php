<?php

class AutoloadingTest extends PHPUnit_Framework_TestCase
{
  /**
   * @dataProvider providerAvailableClasses
   *
   * @param string $className
   */
  public function testCanLoadClass($className)
  {
    $this->assertTrue(
      class_exists($className),
      sprintf(
        'Failed to assert that the class "%s" can be loaded',
        $className
      )
    );
  }

  /**
   * @return array
   */
  public function providerAvailableClasses()
  {
    $classNames = array(
      'Raygun4php\Raygun4PhpException',
      'Raygun4php\RaygunClient',
      'Raygun4php\RaygunClientMessage',
      'Raygun4php\RaygunEnvironmentMessage',
      'Raygun4php\RaygunExceptionMessage',
      'Raygun4php\RaygunExceptionTraceLineMessage',
      'Raygun4php\RaygunIdentifier',
      'Raygun4php\RaygunMessage',
      'Raygun4php\RaygunRequestMessage',
      'Raygun4Php\Rhumsaa\Uuid\Uuid',
      'Raygun4Php\Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException',
      'Raygun4Php\Rhumsaa\Uuid\Exception\UnsupportedOperationException',
    );

    $data = array();

    array_walk($classNames, function ($className) use (&$data) {
        array_push($data, array($className));
    });

    return $data;
  }
}
