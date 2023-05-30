<?php

namespace Drupal\wire\Exceptions;

class MethodNotFoundException extends \Exception {

  public function __construct($method, $component) {
    parent::__construct(
      "Unable to call component method. Public method [{$method}] not found on component: [{$component}]"
    );
  }

}
