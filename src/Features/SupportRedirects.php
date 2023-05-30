<?php

namespace Drupal\wire\Features;

use Drupal\wire\Wire;

class SupportRedirects {

  static function init() {
    return new static;
  }

  function __construct() {
    Wire::listen('component.hydrate', function ($component, $request) {
    });

    Wire::listen('component.dehydrate', function ($component, $response) {

      if (empty($component->redirectTo)) {
        return;
      }

      $response->effects['redirect'] = $component->redirectTo;
    });

    Wire::listen('component.dehydrate.subsequent', function ($component, $response) {
    });

    Wire::listen('flush-state', function () {
    });
  }

}
