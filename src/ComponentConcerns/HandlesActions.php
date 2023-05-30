<?php

namespace Drupal\wire\ComponentConcerns;

use Drupal\wire\Exceptions\MissingFileUploadsTraitException;
use Drupal\wire\Wire;
use Drupal\wire\Exceptions\MethodNotFoundException;
use Drupal\wire\Exceptions\NonPublicComponentMethodCall;
use Drupal\wire\Exceptions\PublicPropertyNotFoundException;
use Drupal\wire\StackMiddleware\HydrationMiddleware\HashDataPropertiesForDirtyDetection;

trait HandlesActions {

  public function syncInput($name, $value, $rehash = TRUE) {
    $propertyName = $this->beforeFirstDot($name);

    $this->callBeforeAndAfterSyncHooks($name, $value, function ($name, $value) use ($propertyName, $rehash) {
      throw_unless(
        $this->propertyIsPublicAndNotDefinedOnBaseClass($propertyName),
        new PublicPropertyNotFoundException($propertyName, $this::getId())
      );

      if ($this->containsDots($name)) {
        // Strip away model name.
        $keyName = $this->afterFirstDot($name);
        // Get model attribute to be filled.
        $targetKey = $this->beforeFirstDot($keyName);

        // Get existing data from model property.
        $results = [];
        $results[$targetKey] = data_get($this->{$propertyName}, $targetKey, []);

        // Merge in new data.
        data_set($results, $keyName, $value);

        // Re-assign data to model.
        data_set($this->{$propertyName}, $targetKey, $results[$targetKey]);
      }
      else {
        $this->{$name} = $value;
      }

      $rehash && HashDataPropertiesForDirtyDetection::rehashProperty($name, $value, $this);
    });
  }

  protected function callBeforeAndAfterSyncHooks($name, $value, $callback) {
    $name = Wire::str($name);

    $propertyName = $name->studly()->before('.');
    $keyAfterFirstDot = $name->contains('.') ? $name->after('.')->__toString() : NULL;
    $keyAfterLastDot = $name->contains('.') ? $name->afterLast('.')->__toString() : NULL;

    $beforeMethod = 'updating' . $propertyName;
    $afterMethod = 'updated' . $propertyName;

    $beforeNestedMethod = $name->contains('.')
      ? 'updating' . $name->replace('.', '_')->studly()
      : FALSE;

    $afterNestedMethod = $name->contains('.')
      ? 'updated' . $name->replace('.', '_')->studly()
      : FALSE;

    $name = $name->__toString();

    $this->updating($name, $value);

    if (method_exists($this, $beforeMethod)) {
      $this->{$beforeMethod}($value, $keyAfterFirstDot);
    }

    if ($beforeNestedMethod && method_exists($this, $beforeNestedMethod)) {
      $this->{$beforeNestedMethod}($value, $keyAfterLastDot);
    }

    Wire::dispatch('component.updating', $this, $name, $value);

    $callback($name, $value);

    $this->updated($name, $value);

    if (method_exists($this, $afterMethod)) {
      $this->{$afterMethod}($value, $keyAfterFirstDot);
    }

    if ($afterNestedMethod && method_exists($this, $afterNestedMethod)) {
      $this->{$afterNestedMethod}($value, $keyAfterLastDot);
    }

    Wire::dispatch('component.updated', $this, $name, $value);
  }

  public function callMethod($method, $params = [], $captureReturnValueCallback = NULL) {
    $method = trim($method);

    switch ($method) {
      case '$sync':
        $prop = array_shift($params);
        $this->syncInput($prop, head($params));

        return;

      case '$set':
        $prop = array_shift($params);
        $this->syncInput($prop, head($params), $rehash = FALSE);

        return;

      case '$toggle':
        $prop = array_shift($params);

        if ($this->containsDots($prop)) {
          $propertyName = $this->beforeFirstDot($prop);
          $targetKey = $this->afterFirstDot($prop);
          $currentValue = data_get($this->{$propertyName}, $targetKey);
        }
        else {
          $currentValue = $this->{$prop};
        }

        $this->syncInput($prop, !$currentValue, $rehash = FALSE);

        return;

      case '$refresh':
        return;
    }

    if (!method_exists($this, $method)) {
      throw_if($method === 'startUpload', new MissingFileUploadsTraitException($this::getId()));
      throw new MethodNotFoundException($method, $this::getId());
    }

    throw_unless($this->methodIsPublicAndNotDefinedOnBaseClass($method), new NonPublicComponentMethodCall($method));

    $returned = call_user_func([$this, $method], ...array_values($params));
    $captureReturnValueCallback && $captureReturnValueCallback($returned);
  }

  protected function methodIsPublicAndNotDefinedOnBaseClass($methodName) {
    return collect((new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC))
        ->reject(function ($method) {
          // The "render" method is a special case. This method might be called by event listeners or other ways.
          if ($method === 'render') {
            return FALSE;
          }

          return $method->class === self::class;
        })
        ->pluck('name')
        ->search($methodName) !== FALSE;
  }

}
