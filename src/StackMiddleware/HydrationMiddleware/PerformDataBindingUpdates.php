<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

class PerformDataBindingUpdates implements HydrationMiddleware {

  public static function hydrate($unHydratedInstance, $request) {
    foreach ($request->updates as $update) {
      if ($update['type'] !== 'syncInput') {
        continue;
      }

      $data = $update['payload'];

      if (!\array_key_exists('value', $data)) {
        continue;
      }

      $unHydratedInstance->syncInput($data['name'], $data['value']);
    }
  }

  public static function dehydrate($instance, $response) {
    //
  }

}
