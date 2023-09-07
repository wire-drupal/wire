<?php

namespace Drupal\wire\Features;

use Drupal\wire\Wire;

class SupportComponentTraits {

  static function init(): static {
    return new static;
  }

  protected array $componentIdMethodMap = [];

  function __construct() {

    Wire::listen('component.boot', function ($component) {

      foreach (\class_uses_recursive($component) as $trait) {
        $hooks = [
          'boot',
          'hydrate',
          'mount',
          'booted',
          'updating',
          'updated',
          'rendering',
          'rendered',
          'dehydrate',
        ];

        foreach ($hooks as $hook) {
          $method = $hook . \class_basename($trait);

          if (\method_exists($component, $method)) {
            $this->componentIdMethodMap[$component->id][$hook][] = [$component, $method];
          }
        }
      }

      $methods = $this->componentIdMethodMap[$component->id]['boot'] ?? [];

      foreach ($methods as $method) {
        \call_user_func([$this, $method]);
      }
    });

    Wire::listen('component.hydrate', function ($component) {
      $component->initializeTraits();

      $methods = $this->componentIdMethodMap[$component->id]['hydrate'] ?? [];

      foreach ($methods as $method) {
        \call_user_func([$this, $method]);
      }
    });

    Wire::listen('component.mount', function ($component, $params) {
      $methods = $this->componentIdMethodMap[$component->id]['mount'] ?? [];

      foreach ($methods as $method) {
        \call_user_func([$this, $method], $params);
      }
    });

    Wire::listen('component.booted', function ($component, $request) {
      $methods = $this->componentIdMethodMap[$component->id]['booted'] ?? [];
      foreach ($methods as $method) {
        \call_user_func([$this, $method], [$request]);
      }
    });

    Wire::listen('component.updating', function ($component, $name, $value) {
      $methods = $this->componentIdMethodMap[$component->id]['updating'] ?? [];
      foreach ($methods as $method) {
        \call_user_func([$this, $method], [$name, $value]);
      }
    });

    Wire::listen('component.updated', function ($component, $name, $value) {
      $methods = $this->componentIdMethodMap[$component->id]['updated'] ?? [];

      foreach ($methods as $method) {
        \call_user_func([$this, $method], [$name, $value]);

      }
    });

    Wire::listen('component.rendering', function ($component) {
      $methods = $this->componentIdMethodMap[$component->id]['rendering'] ?? [];
      foreach ($methods as $method) {
        \call_user_func([$this, $method]);
      }
    });

    Wire::listen('component.rendered', function ($component, $view) {
      $methods = $this->componentIdMethodMap[$component->id]['rendered'] ?? [];
      foreach ($methods as $method) {
        \call_user_func([$this, $method], [$view]);
      }
    });

    Wire::listen('component.dehydrate', function ($component) {

      $methods = $this->componentIdMethodMap[$component->id]['dehydrate'] ?? [];

      foreach ($methods as $method) {
        \call_user_func([$this, $method]);

      }
    });

    Wire::listen('flush-state', function () {
      $this->componentIdMethodMap = [];
    });
  }

}
