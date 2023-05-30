<?php

namespace Drupal\wire\Exceptions;

class MissingRulesException extends \Exception {

  public function __construct($component) {
    parent::__construct(
      "Missing [\$rules/rules()] property/method on Wire component: [{$component}]."
    );
  }

}
