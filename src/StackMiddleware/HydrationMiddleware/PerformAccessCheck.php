<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

use Drupal\wire\Exceptions\AccessDeniedException;
use Drupal\wire\WireComponentInterface;

class PerformAccessCheck implements HydrationMiddleware
{

  public static function mountAccess($instance, $request): void
  {
    if ($instance instanceof WireComponentInterface) {
      if (!$instance->access()) {
        throw new AccessDeniedException($instance->getId());
      }
    }
  }

  public static function hydrate($instance, $request): void
  {
    if ($instance instanceof WireComponentInterface) {
      if (!$instance->access()) {
        throw new AccessDeniedException($instance->getId());
      }
    }
  }

  public static function dehydrate($instance, $response): void
  {

  }
}
