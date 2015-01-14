<?php

class AutoloadingTest extends PHPUnit_Framework_TestCase
{
  /**
   * @dataProvider providerAvailableClasses
   *
   * @param string $className
   */
  public function testCanAutoloadClass($className)
  {
    $this->assertTrue(class_exists($className), sprintf(
      'Failed asserting that the class "%s" can be autoloaded.',
      $className
    ));
  }

  /**
   * @return string[]
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

    return array_map(function ($className) {
      return array(
        $className,
      );
    }, $classNames);
  }
}
