<?php

namespace Drupal\wire\Features;

use Drupal\wire\Wire;
use Drupal\wire\Wireable;
use Drupal\wire\WithFileUploads;
use Drupal\Wire\TemporaryUploadedFile;

class SupportFileUploads {

  static function init(): static {
    return new static;
  }

  function __construct() {
    Wire::listen('property.hydrate', function ($property, $value, $component, $request) {
      $uses = \array_flip(\class_uses_recursive($component));

      if (!\in_array(WithFileUploads::class, $uses)) {
        return;
      }

      if (TemporaryUploadedFile::canUnserialize($value)) {
        $component->{$property} = TemporaryUploadedFile::unserializeFromWireRequest($value);
      }
    });

    Wire::listen('property.dehydrate', function ($property, $value, $component, $response) {
      $uses = \array_flip(\class_uses_recursive($component));

      if (!\in_array(WithFileUploads::class, $uses)) {
        return;
      }

      $newValue = $this->dehydratePropertyFromWithFileUploads($value);

      if ($newValue !== $value) {
        $component->{$property} = $newValue;
      }
    });
  }

  public function dehydratePropertyFromWithFileUploads($value) {
    if (TemporaryUploadedFile::canUnserialize($value)) {
      return TemporaryUploadedFile::unserializeFromWireRequest($value);
    }

    if ($value instanceof TemporaryUploadedFile) {
      return $value->serializeForWireResponse();
    }

    if (\is_array($value) && isset(\array_values($value)[0])) {
      $isValid = TRUE;

      foreach ($value as $key => $arrayValue) {
        if (!($arrayValue instanceof TemporaryUploadedFile) || !\is_numeric($key)) {
          $isValid = FALSE;
          break;
        }
      }

      if ($isValid) {
        return \array_values($value)[0]::serializeMultipleForWireResponse($value);
      }
    }

    if (\is_array($value)) {
      foreach ($value as $key => $item) {
        $value[$key] = $this->dehydratePropertyFromWithFileUploads($item);
      }
    }

    if ($value instanceof Wireable) {
      $keys = \array_keys(\get_object_vars($value));

      foreach ($keys as $key) {
        $value->{$key} = $this->dehydratePropertyFromWithFileUploads($value->{$key});
      }
    }

    return $value;
  }

}
