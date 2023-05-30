<?php

namespace Drupal\wire;

class Event {

  protected $up;

  protected $self;

  protected $component;

  public function __construct(protected string $name, protected array $params) { }

  public function up(): static {
    $this->up = TRUE;

    return $this;
  }

  public function self(): static {
    $this->self = TRUE;

    return $this;
  }

  public function component($name): static {
    $this->component = $name;

    return $this;
  }

  public function to(): static {
    return $this;
  }

  public function serialize(): array {
    $output = [
      'event' => $this->name,
      'params' => $this->params,
    ];

    if ($this->up) {
      $output['ancestorsOnly'] = TRUE;
    }
    if ($this->self) {
      $output['selfOnly'] = TRUE;
    }
    if ($this->component) {
      $output['to'] = is_subclass_of($this->component, WireComponent::class)
        ? $this->component::getId()
        : $this->component;
    }

    return $output;
  }

}
