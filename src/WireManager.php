<?php

namespace Drupal\wire;

use Drupal\Component\Serialization\Json;
use Drupal\wire\Exceptions\AccessDeniedException;

class WireManager {

  protected array $listeners = [];

  protected static bool $debug;

  public static function mount($name, array $params = []): ?LifecycleManager {

    $id = Wire::str()->random(20);

    try {
      return LifecycleManager::fromInitialRequest($name, $id)
        ->boot()
        ->initialHydrate()
        ->mount($params)
        ->renderToView()
        ->initialDehydrate()
        ->toInitialResponse();
    }
    catch (AccessDeniedException) {
      return NULL;
    }
  }

  public function dispatch($event, ...$params): void {
    foreach ($this->listeners[$event] ?? [] as $listener) {
      $listener(...$params);
    }
  }

  public function listen($event, $callback): void {
    $this->listeners[$event][] = $callback;
  }

  public function originalPath() {
    if ($this->isDefinitelyWireRequest()) {
      $payload = Wire::getPayloadFromRequest();
      return \data_get($payload, 'fingerprint.path');
    }

    return \Drupal::request()->getUri();
  }

  public function originalMethod() {
    if ($this->isDefinitelyWireRequest()) {
      $payload = Wire::getPayloadFromRequest();
      return \data_get($payload, 'fingerprint.method');
    }

    return \Drupal::request()->getMethod();
  }

  public function isDefinitelyWireRequest(): bool {
    return \Drupal::service('current_route_match')->getRouteName() === 'wire.message';
  }

  public function getPayloadFromRequest($request = NULL) {
    $request ??= \Drupal::request();
    return Json::decode($request->getContent());
  }

}
