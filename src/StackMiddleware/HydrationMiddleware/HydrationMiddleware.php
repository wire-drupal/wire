<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

interface HydrationMiddleware {

  public static function hydrate($instance, $request);

  public static function dehydrate($instance, $response);

}
