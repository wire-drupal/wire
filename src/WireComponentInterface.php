<?php

namespace Drupal\wire;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * An interface for single file components.
 */
interface WireComponentInterface extends PluginInspectionInterface {

  /**
   * Gets the id of the component.
   *
   * This should only contain alphanumeric characters and underscores.
   *
   * @return string
   *   The id of the component.
   */
  public function getId(): string;

}
