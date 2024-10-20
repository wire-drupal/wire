<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

use Drupal\wire\Exceptions\AccessDeniedException;
use Drupal\wire\WireComponentInterface;

class PerformAccessCheck implements HydrationMiddleware {

  public static function mountAccess($instance, $request): void {
    if ($instance instanceof WireComponentInterface) {
      self::handleAccessCheck($instance);
    }
  }

  public static function hydrate($instance, $request): void {
    if ($instance instanceof WireComponentInterface) {
      self::handleAccessCheck($instance);
    }
  }

  public static function dehydrate($instance, $response): void {}

  /**
   * @throws AccessDeniedException
   */
  private static function handleAccessCheck($instance): void {
    $accessResult = $instance->access();
    if ($accessResult === FALSE) {
      throw new AccessDeniedException($instance->getId());
    }
  }

}
