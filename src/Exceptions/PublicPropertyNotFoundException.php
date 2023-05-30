<?php

namespace Drupal\wire\Exceptions;

class PublicPropertyNotFoundException extends \Exception {

  public function __construct($property, $component) {
    parent::__construct(
      "Unable to set component data. Public property [\${$property}] not found on component: [{$component}]"
    );
  }

}
