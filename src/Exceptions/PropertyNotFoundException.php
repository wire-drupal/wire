<?php

namespace Drupal\wire\Exceptions;

class PropertyNotFoundException extends \Exception {

  public function __construct($property, $component) {
    parent::__construct(
      "Property [\${$property}] not found on component: [{$component}]"
    );
  }

}
