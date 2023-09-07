<?php

namespace Drupal\wire;

use Illuminate\Support\Str;
use ReflectionClass;

class Wire {

  public static function __callStatic($method, $args) {

    $container = \Drupal::getContainer();
    if (!$container->has('wire_manager')) {
      $wireManager = new WireManager();
      $container->set('wire_manager', $wireManager);
    }

    $instance = $container->get('wire_manager');
    return $instance->$method(...$args);
  }

  public static function str($string = NULL) {
    if (\is_null($string)) {
      return new class {

        public function __call($method, $params) {
          return Str::$method(...$params);
        }

      };
    }

    return Str::of($string);
  }

  public static function invade($obj) {
    return new class($obj) {

      public $reflected;

      public function __construct(public object $obj) {
        $this->reflected = new ReflectionClass($obj);
      }

      public function __get($name) {
        $property = $this->reflected->getProperty($name);

        return $property->getValue($this->obj);
      }

      public function __set($name, $value) {
        $property = $this->reflected->getProperty($name);

        $property->setValue($this->obj, $value);
      }

      public function __call($name, $params) {
        $method = $this->reflected->getMethod($name);

        return $method->invoke($this->obj, ...$params);
      }

    };
  }

}
