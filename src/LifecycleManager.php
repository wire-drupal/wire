<?php

namespace Drupal\wire;

use Drupal\wire\Plugin\WirePluginManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\wire\Request as WireRequest;
use Drupal\wire\Response as WireResponse;

class LifecycleManager {

  protected static ?RequestStack $requestStack;

  protected static ?WirePluginManager $wirePluginManager;

  protected static ?Request $currentRequest;

  protected static array $hydrationMiddleware = [];

  protected static array $initialHydrationMiddleware = [];

  protected static array $initialDehydrationMiddleware = [];

  public ?WireComponent $instance;

  public WireRequest $request;

  public ?WireResponse $response;

  public static function wirePluginManager() {
    if (!isset(self::$wirePluginManager)) {
      self::$wirePluginManager = \Drupal::service('plugin.manager.wire');
    }
    return self::$wirePluginManager;
  }

  public static function requestStack() {
    if (!isset(self::$requestStack)) {
      self::$requestStack = \Drupal::service('request_stack');
    }
    return self::$requestStack;
  }

  public static function currentRequest() {
    if (!isset(self::$currentRequest)) {
      self::$currentRequest = self::requestStack()->getCurrentRequest();
    }
    return self::$currentRequest;
  }

  public static function registerHydrationMiddleware(array $classes): void {
    static::$hydrationMiddleware += $classes;
  }

  public static function registerInitialHydrationMiddleware(array $callables): void {
    static::$initialHydrationMiddleware += $callables;
  }

  public static function registerInitialDehydrationMiddleware(array $callables): void {
    static::$initialDehydrationMiddleware += $callables;
  }

  public static function fromInitialRequest($name, $id) {
    return tap(new static, function ($instance) use ($name, $id) {

      $pluginInstance = self::wirePluginManager()->createInstance($name, ['uniqueId' => $id]);
      assert($pluginInstance instanceof WireComponent);
      $instance->instance = $pluginInstance;

      $instance->request = new WireRequest([
        'fingerprint' => [
          'id' => $id,
          'name' => $name,
          'locale' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
          'path' => self::currentRequest()->getPathInfo(),
          'method' => self::currentRequest()->getMethod(),
        ],
        'updates' => [],
        'serverMemo' => [],
      ]);
    });
  }

  public function boot() {
    Wire::dispatch('component.boot', $this->instance);
    return $this;
  }

  public function hydrate(): static {
    foreach (static::$hydrationMiddleware as $class) {
      $class::hydrate($this->instance, $this->request);
    }

    return $this;
  }

  public function dehydrate(): static {
    $this->response = WireResponse::fromRequest($this->request);

    // The array is being reversed here, so the middleware dehydrate phase order of execution is
    // the inverse of hydrate. This makes the middlewares behave like layers in a shell.
    foreach (array_reverse(static::$hydrationMiddleware) as $class) {
      $class::dehydrate($this->instance, $this->response);
    }

    return $this;
  }

  public function initialHydrate(): static {
    foreach (static::$initialHydrationMiddleware as $callable) {
      $callable($this->instance, $this->request);
    }

    return $this;
  }

  public function mount($params = []): static {

    // Assign all public component properties that have matching parameters.
    collect(array_intersect_key($params, $this->instance->getPublicPropertiesDefinedBySubClass()))
      ->each(function ($value, $property) {
        $this->instance->{$property} = $value;
      });

    if (method_exists($this->instance, 'mount')) {
      call_user_func([$this->instance, 'mount'], ...array_values($params));
    }

    Wire::dispatch('component.mount', $this->instance, $params);
    Wire::dispatch('component.booted', $this->instance, $this->request);

    return $this;
  }

  public static function fromSubsequentRequest($payload) {

    return tap(new static, function ($instance) use ($payload) {
      $instance->request = new WireRequest($payload);

      $pluginInstance = self::wirePluginManager()->createInstance(
        $instance->request->name(),
        ['uniqueId' => $instance->request->id()]
      );

      assert($pluginInstance instanceof WireComponent);
      $instance->instance = $pluginInstance;
    });
  }

  public function renderToView(): static {
    $this->instance->renderToView();

    return $this;
  }

  public function initialDehydrate(): static {
    $this->response = Response::fromRequest($this->request);

    foreach (array_reverse(static::$initialDehydrationMiddleware) as $callable) {
      $callable($this->instance, $this->response);
    }

    return $this;
  }

  public function toInitialResponse(): static {
    $this->response->embedThyselfInHtml();
    Wire::dispatch('mounted', $this->response);
    $this->response->toInitialResponse();

    return $this;
  }

  public function toSubsequentResponse(): array {
    return $this->response->toSubsequentResponse();
  }

  public function html() {
    return $this->response->effects['html'];
  }

  public function cache() {
    return $this->response->cache;
  }

}
