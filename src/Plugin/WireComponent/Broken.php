<?php

namespace Drupal\wire\Plugin\WireComponent;

use Drupal\wire\Plugin\Attribute\WireComponent;
use Drupal\wire\WireComponentBase;
use Drupal\wire\View;

#[WireComponent(id: 'broken')]
class Broken extends WireComponentBase
{
  public bool $isStateless = TRUE;

  public function render(): View {
    return View::fromString('<div>Missing component</div>');
  }

}
