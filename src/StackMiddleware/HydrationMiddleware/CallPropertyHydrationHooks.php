<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

use Drupal\wire\Wire;

class CallPropertyHydrationHooks implements HydrationMiddleware {

  public static function hydrate($instance, $request) {
    $publicProperties = $instance->getPublicPropertiesDefinedBySubClass();

    foreach ($publicProperties as $property => $value) {
      Wire::dispatch('property.hydrate', $property, $value, $instance, $request);

      // Call magic hydrateProperty methods on the component.
      // If the method doesn't exist, the __call will eat it.
      $studlyProperty = \str_replace(' ', '', \ucwords(\str_replace(['-', '_'], ' ', $property)));
      $method = 'hydrate' . $studlyProperty;
      $instance->{$method}($value, $request);
    }
  }

  public static function dehydrate($instance, $response) {
    $publicProperties = $instance->getPublicPropertiesDefinedBySubClass();

    foreach ($publicProperties as $property => $value) {
      $studlyProperty = \str_replace(' ', '', \ucwords(\str_replace(['-', '_'], ' ', $property)));
      $method = 'dehydrate' . $studlyProperty;
      $instance->{$method}($value, $response);

      Wire::dispatch('property.dehydrate', $property, $value, $instance, $response);
    }
  }

}
