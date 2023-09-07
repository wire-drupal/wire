<?php

namespace Drupal\wire\Features;

use Drupal\wire\Wire;

class OptimizeRenderedDom {

  static function init() {
    return new static;
  }

  protected $htmlHashesByComponent = [];

  function __construct() {

    Wire::listen('component.dehydrate.initial', function ($component, $response) {
      $response->memo['htmlHash'] = \hash('crc32b', $response->effects['html'] ?? '');
    });

    Wire::listen('component.hydrate.subsequent', function ($component, $request) {
      $this->htmlHashesByComponent[$component->id] = $request->memo['htmlHash'];
    });

    Wire::listen('component.dehydrate.subsequent', function ($component, $response) {
      $oldHash = $this->htmlHashesByComponent[$component->id] ?? NULL;

      $response->memo['htmlHash'] = $newHash = \hash('crc32b', $response->effects['html'] ?? '');

      if ($oldHash === $newHash) {
        $response->effects['html'] = NULL;
      }
    });

    Wire::listen('flush-state', function () {
      $this->htmlHashesByComponent = [];
    });
  }

}
