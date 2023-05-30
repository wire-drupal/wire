<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

class RenderView implements HydrationMiddleware {

  public static function hydrate($unHydratedInstance, $request) {
    //
  }

  public static function dehydrate($instance, $response) {
    $html = $instance->output();

    data_set($response, 'effects.html', $html);
    // Flag that there is no need to keep track of state changes.
    data_set($response, 'isStateless', $instance->isStateless);
  }

}
