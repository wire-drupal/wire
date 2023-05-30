<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

use Illuminate\Support\Collection;

class NormalizeComponentPropertiesForJavaScript extends NormalizeDataForJavaScript implements HydrationMiddleware {

  public static function hydrate($instance, $request) {
    //
  }

  public static function dehydrate($instance, $response) {
    foreach ($instance->getPublicPropertiesDefinedBySubClass() as $key => $value) {
      if (is_array($value) || $value instanceof Collection) {
        $instance->$key = static::reindexArrayWithNumericKeysOtherwiseJavaScriptWillMessWithTheOrder($value);
      }
    }
  }

}
