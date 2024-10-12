<?php

namespace Drupal\wire\Plugin;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\wire\Plugin\Attribute\WireComponent;
use Drupal\wire\WireComponentInterface;

class WirePluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $moduleHandler) {
    parent::__construct(
      'Plugin/WireComponent',
      $namespaces,
      $moduleHandler,
      WireComponentInterface::class,
      WireComponent::class,
      'Drupal\wire\Plugin\Annotation\WireComponent'
    );

    $this->alterInfo('wire_component_plugin_info');
    $this->setCacheBackend($cacheBackend, 'wire_component_plugins');
  }

  public function getFallbackPluginId($plugin_id, array $configuration = []): string {
    return 'broken';
  }

}
