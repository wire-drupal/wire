<?php

namespace Drupal\wire\ComponentConcerns;

use Illuminate\Support\MessageBag;
use Drupal\wire\Wire;
use function collect;

trait ValidatesInput {

  protected ?MessageBag $errorBag;

  public function getErrorBag(): MessageBag {
    return $this->errorBag ?? new MessageBag;
  }

  public function addError($name, $message): MessageBag {
    return $this->getErrorBag()->add($name, $message);
  }

  public function setErrorBag($bag): MessageBag {
    return $this->errorBag = $bag instanceof MessageBag
      ? $bag
      : new MessageBag($bag);
  }

  public function resetErrorBag($field = NULL) {
    $fields = (array) $field;

    if (empty($fields)) {
      return $this->errorBag = new MessageBag;
    }

    $this->setErrorBag(
      $this->errorBagExcept($fields)
    );
  }

  public function clearValidation($field = NULL): void {
    $this->resetErrorBag($field);
  }

  public function resetValidation($field = NULL): void {
    $this->resetErrorBag($field);
  }

  public function errorBagExcept($field): MessageBag {
    $fields = (array) $field;

    return new MessageBag(
      collect($this->getErrorBag())
        ->reject(function ($messages, $messageKey) use ($fields) {
          return collect($fields)->some(function ($field) use ($messageKey) {
            return Wire::str($messageKey)->is($field);
          });
        })
        ->toArray()
    );
  }

  public function hasErrors(): bool {
    return $this->getErrorBag()->count() !== 0;
  }

}
