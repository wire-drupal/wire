<?php

declare(strict_types=1);

namespace Drupal\wire\Plugin\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation object for wire.
 *
 * @Annotation
 */
class WireComponent extends Plugin {

  /**
   * The ID of the WireComponent plugin.
   */
  public string $id;

  /**
   * The Label of the WireComponent plugin.
   */
  public ?string $label = NULL;

}
