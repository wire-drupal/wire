<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

use Drupal\wire\Wire;

class CallHydrationHooks implements HydrationMiddleware {

  public static function hydrate($instance, $request) {


    Wire::dispatch('component.hydrate', $instance, $request);
    Wire::dispatch('component.hydrate.subsequent', $instance, $request);

    $instance->hydrate($request);

    Wire::dispatch('component.booted', $instance, $request);
  }

  public static function dehydrate($instance, $response) {
    $instance->dehydrate($response);

    Wire::dispatch('component.dehydrate', $instance, $response);
    Wire::dispatch('component.dehydrate.subsequent', $instance, $response);
  }

  public static function initialDehydrate($instance, $response) {
    $instance->dehydrate($response);

    Wire::dispatch('component.dehydrate', $instance, $response);
    Wire::dispatch('component.dehydrate.initial', $instance, $response);
  }

  public static function initialHydrate($instance, $request) {
    Wire::dispatch('component.hydrate', $instance, $request);
    Wire::dispatch('component.hydrate.initial', $instance, $request);
  }

}
