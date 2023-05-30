<?php

namespace Drupal\wire\ComponentConcerns;

use Drupal\Core\Url;

trait PerformsRedirects {

  public ?string $redirectTo;

  public function redirect($url): static {
    $this->redirectTo = $url;
    $this->shouldSkipRender = TRUE;
    return $this;
  }

  public function redirectRoute($name, $parameters = [], $options = []): static {
    $this->redirectTo = Url::fromRoute($name, $parameters, $options)->toString();
    $this->shouldSkipRender = TRUE;
    return $this;
  }

}
