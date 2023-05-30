<?php

namespace Drupal\wire\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class WirePluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $moduleHandler) {
    parent::__construct(
      'Plugin/WireComponent',
      $namespaces,
      $moduleHandler,
      'Drupal\wire\WireComponentInterface',
      'Drupal\wire\Annotation\WireComponent'
    );

    $this->setCacheBackend($cacheBackend, 'wire_component_plugins');
  }

}
