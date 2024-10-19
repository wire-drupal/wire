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

  /**
   * Checks access for the Wire component.
   *
   * @return bool
   *   TRUE if access is allowed, FALSE otherwise.
   *
   * @todo: Decide if Drupal\Core\Access\AccessResult should be expected here.
   */
  public function access(): bool;

}
