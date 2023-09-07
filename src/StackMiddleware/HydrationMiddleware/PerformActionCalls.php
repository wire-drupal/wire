<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

use Drupal\wire\Exceptions\DirectlyCallingLifecycleHooksNotAllowedException;
use Drupal\wire\Wire;

class PerformActionCalls implements HydrationMiddleware {

  public const PROTECTED_METHODS = [
    'mount',
    'hydrate*',
    'dehydrate*',
    'updating*',
    'updated*',
  ];

  public static function hydrate($unHydratedInstance, $request) {
    foreach ($request->updates as $update) {
      if ($update['type'] !== 'callMethod') {
        continue;
      }

      $id = $update['payload']['id'];
      $method = $update['payload']['method'];
      $params = $update['payload']['params'];

      \throw_if(
        Wire::str($method)->is(static::PROTECTED_METHODS),
        new DirectlyCallingLifecycleHooksNotAllowedException($method, $unHydratedInstance->getId())
      );

      $unHydratedInstance->callMethod($method, $params, function ($returned) use ($unHydratedInstance, $method, $id) {
        Wire::dispatch('action.returned', $unHydratedInstance, $method, $returned, $id);
      });
    }
  }

  public static function dehydrate($instance, $response) {
    //
  }

}
