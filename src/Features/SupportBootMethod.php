<?php

namespace Drupal\wire\Features;

use Drupal\wire\Wire;

class SupportBootMethod {

  static function init() {
    return new static;
  }

  function __construct() {
    Wire::listen('component.boot', function ($component) {
      $component->bootIfNotBooted();
    });

    Wire::listen('component.booted', function ($component, $request) {
      if (\method_exists($component, $method = 'booted')) {
        \call_user_func([$component, $method], [$request]);
      }
    });
  }

}
