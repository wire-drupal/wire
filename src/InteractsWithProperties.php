<?php

namespace Drupal\wire;

trait InteractsWithProperties {

  public function handleHydrateProperty($property, $value) {
    $newValue = $value;

    if (method_exists($this, 'hydrateProperty')) {
      $newValue = $this->hydrateProperty($property, $newValue);
    }

    foreach (array_diff(class_uses_recursive($this), class_uses(self::class)) as $trait) {
      $method = 'hydratePropertyFrom' . class_basename($trait);

      if (method_exists($this, $method)) {
        $newValue = $this->{$method}($property, $newValue);
      }
    }

    return $newValue;
  }

  public function handleDehydrateProperty($property, $value) {
    $newValue = $value;

    if (method_exists($this, 'dehydrateProperty')) {
      $newValue = $this->dehydrateProperty($property, $newValue);
    }

    foreach (array_diff(class_uses_recursive($this), class_uses(self::class)) as $trait) {
      $method = 'dehydratePropertyFrom' . class_basename($trait);

      if (method_exists($this, $method)) {
        $newValue = $this->{$method}($property, $newValue);
      }
    }

    return $newValue;
  }

  public function getPublicPropertiesDefinedBySubClass() {
    $publicProperties = array_filter((new \ReflectionObject($this))->getProperties(), function ($property) {
      return $property->isPublic() && !$property->isStatic();
    });

    $data = [];

    foreach ($publicProperties as $property) {
      if ($property->getDeclaringClass()->getName() !== self::class) {
        $data[$property->getName()] = $this->getInitializedPropertyValue($property);
      }
    }

    return $data;
  }

  public function getProtectedOrPrivatePropertiesDefinedBySubClass() {
    $properties = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);
    $data = [];

    foreach ($properties as $property) {
      if ($property->getDeclaringClass()->getName() !== self::class) {
        $data[$property->getName()] = $this->getInitializedPropertyValue($property);
      }
    }

    return $data;
  }

  public function getInitializedPropertyValue(\ReflectionProperty $property) {
    // Ensures typed property is initialized in PHP >=7.4, if so, return its value,
    // if not initialized, return null (as expected in earlier PHP Versions)
    if (method_exists($property, 'isInitialized') && !$property->isInitialized($this)) {
      return NULL;
    }

    return $property->getValue($this);
  }

  public function hasProperty($prop) {
    return property_exists(
      $this,
      $this->beforeFirstDot($prop)
    );
  }

  public function getPropertyValue($name) {
    $value = $this->{$this->beforeFirstDot($name)};

    if ($this->containsDots($name)) {
      return data_get($value, $this->afterFirstDot($name));
    }

    return $value;
  }

  public function setProtectedPropertyValue($name, $value) {
    return $this->{$name} = $value;
  }

  public function containsDots($subject) {
    return strpos($subject, '.') !== FALSE;
  }

  public function beforeFirstDot($subject) {
    return head(explode('.', $subject));
  }

  public function afterFirstDot($subject): string {
    return str($subject)->after('.');
  }

  public function propertyIsPublicAndNotDefinedOnBaseClass($propertyName) {
    return collect((new \ReflectionObject($this))->getProperties(\ReflectionMethod::IS_PUBLIC))
        ->reject(function ($property) {
          return $property->class === self::class;
        })
        ->pluck('name')
        ->search($propertyName) !== FALSE;
  }

  public function fill($values) {
    $publicProperties = array_keys($this->getPublicPropertiesDefinedBySubClass());

    foreach ($values as $key => $value) {
      if (in_array($this->beforeFirstDot($key), $publicProperties)) {
        data_set($this, $key, $value);
      }
    }
  }

  public function reset(...$properties): void {
    $propertyKeys = array_keys($this->getPublicPropertiesDefinedBySubClass());

    // Keys to reset from array.
    if (count($properties) && is_array($properties[0])) {
      $properties = $properties[0];
    }

    // Reset all.
    if (empty($properties)) {
      $properties = $propertyKeys;
    }

    $freshInstance = \Drupal::service('plugin.manager.wire')
      ->createInstance($this->getPluginId(), ['uniqueId' => Wire::str()->random(20)]);

    foreach ($properties as $property) {
      $this->{$property} = $freshInstance->{$property};
    }
  }

  protected function resetExcept(...$properties) {
    if (count($properties) && is_array($properties[0])) {
      $properties = $properties[0];
    }

    $keysToReset = array_diff(array_keys($this->getPublicPropertiesDefinedBySubClass()), $properties);
    $this->reset($keysToReset);
  }

  public function only($properties) {
    $results = [];

    foreach ($properties as $property) {
      $results[$property] = $this->hasProperty($property) ? $this->getPropertyValue($property) : NULL;
    }

    return $results;
  }

  public function except($properties) {
    return array_diff_key($this->all(), array_flip($properties));
  }

  public function all() {
    return $this->getPublicPropertiesDefinedBySubClass();
  }

}
