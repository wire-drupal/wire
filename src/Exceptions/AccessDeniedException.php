<?php

namespace Drupal\wire\Exceptions;

class AccessDeniedException extends \Exception {

  public function __construct($component) {
    parent::__construct(
      "Access denied for [{$component}] component."
    );
  }

}
