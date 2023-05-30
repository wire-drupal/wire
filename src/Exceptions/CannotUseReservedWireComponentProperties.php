<?php

namespace Drupal\wire\Exceptions;

class CannotUseReservedWireComponentProperties extends \Exception {

  public function __construct($propertyName, $componentName) {
    parent::__construct(
      "Public property [{$propertyName}] on [{$componentName}] component is reserved for internal Wire use."
    );
  }

}
