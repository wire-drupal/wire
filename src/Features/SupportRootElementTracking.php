<?php

namespace Drupal\wire\Features;

use Drupal\wire\Wire;

class SupportRootElementTracking {

  static function init() {
    return new static;
  }

  function __construct() {
    Wire::listen('component.dehydrate.initial', function ($component, $response) {
      if (!$html = \data_get($response, 'effects.html')) {
        return;
      }

      \data_set($response, 'effects.html', $this->addComponentEndingMarker($html, $component));
    });
  }

  public function addComponentEndingMarker($html, $component): string {
    return $html . "\n<!-- wire-end:" . $component->id . ' -->';
  }

}
