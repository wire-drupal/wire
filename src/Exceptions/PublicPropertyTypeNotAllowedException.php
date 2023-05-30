<?php

namespace Drupal\wire\Exceptions;

class PublicPropertyTypeNotAllowedException extends \Exception {

  public function __construct($componentName, $key, $value) {
    parent::__construct(
      "Wire component's [{$componentName}] public property [{$key}] must be of type: [numeric, string, array, null, or boolean].\n" .
      "Only protected or private properties can be set as other types because JavaScript doesn't need to access them. Access is via your mount() method"
    );
  }

}
