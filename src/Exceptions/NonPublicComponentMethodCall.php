<?php

namespace Drupal\wire\Exceptions;

class NonPublicComponentMethodCall extends \Exception {

  public function __construct($method) {
    parent::__construct('Component method not found: [' . $method . ']');
  }

}
