<?php

declare(strict_types=1);

namespace Drupal\wire\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

#[\Attribute(\Attribute::TARGET_CLASS)]
class WireComponent extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?string $label = NULL,
  ) {}

}
