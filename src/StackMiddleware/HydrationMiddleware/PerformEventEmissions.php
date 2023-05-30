<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

class PerformEventEmissions implements HydrationMiddleware {

  public static function hydrate($unHydratedInstance, $request) {
    foreach ($request->updates as $update) {
      if ($update['type'] !== 'fireEvent') {
        continue;
      }

      $id = $update['payload']['id'];
      $event = $update['payload']['event'];
      $params = $update['payload']['params'];

      $unHydratedInstance->fireEvent($event, $params, $id);
    }
  }

  public static function dehydrate($instance, $response) {
    //
  }

}
