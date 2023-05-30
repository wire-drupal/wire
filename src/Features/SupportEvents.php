<?php

namespace Drupal\wire\Features;

use Drupal\wire\Wire;

class SupportEvents {

  static function init() {
    return new static;
  }

  function __construct() {
    Wire::listen('component.hydrate', function ($component, $request) {
      //
    });

    Wire::listen('component.dehydrate.initial', function ($component, $response) {
      $response->effects['listeners'] = $component->getEventsBeingListenedFor();
    });

    Wire::listen('component.dehydrate', function ($component, $response) {

      $emits = $component->getEventQueue();
      $dispatches = $component->getDispatchQueue();

      if ($emits) {
        $response->effects['emits'] = $emits;
      }

      if ($dispatches) {
        $response->effects['dispatches'] = $dispatches;
      }
    });
  }

}
