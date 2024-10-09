<?php

namespace Drupal\wire;

/**
 * Base class for Wire components.
 *
 * Needed for backward compatibility as "WireComponent" class name
 * has been assigned to Plugin Attribute definition.
 *
 * @deprecated in v1.2.0; Use 'Drupal\wire\WireComponentBase' class instead.
 */
class WireComponent extends WireComponentBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error('Extending Drupal\wire\WireComponent is deprecated, use Drupal\wire\WireComponentBase', E_USER_DEPRECATED);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
