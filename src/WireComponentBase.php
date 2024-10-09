<?php

namespace Drupal\wire;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\wire\Exceptions\CannotUseReservedWireComponentProperties;
use Drupal\wire\Exceptions\PropertyNotFoundException;
use Illuminate\Support\MessageBag;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Illuminate\Support\Traits\Macroable;

/**
 * Base class for Wire components.
 */
class WireComponentBase extends PluginBase implements WireComponentInterface, ContainerFactoryPluginInterface {

  use InteractsWithProperties,
    ComponentConcerns\ValidatesInput,
    ComponentConcerns\HandlesActions,
    ComponentConcerns\ReceivesEvents,
    ComponentConcerns\PerformsRedirects,
    Macroable {
    __call as macroCall;
  }

  public string $id;

  public bool $isStateless = FALSE;

  protected array $queryString = [];

  protected array $computedPropertyCache = [];

  protected ?bool $shouldSkipRender = NULL;

  protected ?View $preRenderedView;

  public array $wireCache = ['#cache' => ['tags' => [], 'contexts' => []]];

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->id ??= $configuration['uniqueId'] ?? $this->getId();
    $this->ensureIdPropertyIsntOverridden();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->getPluginId();
  }

  protected function ensureIdPropertyIsntOverridden(): void {
    \throw_if(
      \array_key_exists('id', $this->getPublicPropertiesDefinedBySubClass()),
      new CannotUseReservedWireComponentProperties('id', $this::getId())
    );
  }

  function __isset($property) {
    try {
      $value = $this->__get($property);

      if (isset($value)) {
        return TRUE;
      }
    } catch (PropertyNotFoundException $ex) {
    }

    return FALSE;
  }

  public function __get($property) {
    $studlyProperty = \str_replace(' ', '', \ucwords(\str_replace(['-', '_'], ' ', $property)));

    if (\method_exists($this, $computedMethodName = 'get' . $studlyProperty . 'Property')) {
      if (isset($this->computedPropertyCache[$property])) {
        return $this->computedPropertyCache[$property];
      }
      return $this->computedPropertyCache[$property] = \call_user_func([$this, $computedMethodName]);
    }

    throw new PropertyNotFoundException($property, $this->getId());
  }

  public function __call($method, $params) {
    if (
      \in_array($method, ['mount', 'hydrate', 'dehydrate', 'updating', 'updated'])
      || \str($method)->startsWith(['updating', 'updated', 'hydrate', 'dehydrate'])
    ) {
      // Eat calls to the lifecycle hooks if the dev didn't define them.
      return;
    }

    if (static::hasMacro($method)) {
      return $this->macroCall($method, $params);
    }

    throw new \BadMethodCallException(\sprintf(
      'Method %s::%s does not exist.', static::class, $method
    ));
  }

  public function renderToView(): static {

    Wire::dispatch('component.rendering', $this);

    if (!\method_exists($this, 'render')) {
      throw new \Exception('Please implement the "render" method on [' . \get_class($this) . ']');
    }

    $view = \call_user_func([$this, 'render']);

    // Skip handling this component.
    if ($view === NULL) {
      $this->preRenderedView = $view;
      $this->shouldSkipRender = TRUE;
      return $this;
    }

    if (\is_string($view)) {
      $view = View::fromString($view, $this->getPublicPropertiesDefinedBySubClass());
    }

    \throw_unless($view instanceof View,
      new \Exception('"render" method on [' . \get_class($this) . '] must return twig string'));

    Wire::dispatch('component.rendered', $this, $view);

    $this->preRenderedView = $view;

    return $this;
  }

  public function output() {
    if ($this->shouldSkipRender) {
      return NULL;
    }

    $view = $this->preRenderedView;

    $rendered = $view->render(
      $this->getPublicPropertiesDefinedBySubClass() + [
        '__wire_id' => $this->id,
        '__wire_cache' => $this->getWireCache(),
        '__wire_assets' => !$this->isStateless,
        '__wire_errors' => $this->getErrorBag(),
      ]
    );

    // Special directives replacement.
    return !$this->isStateless
      ? \str_replace('@this', "window.wire.find('" . $this->id . "')", $rendered)
      : $rendered;
  }

  public function forgetComputed($key = NULL): void {
    if (\is_null($key)) {
      $this->computedPropertyCache = [];
      return;
    }

    $keys = \is_array($key) ? $key : \func_get_args();

    \collect($keys)->each(function ($i) {
      if (isset($this->computedPropertyCache[$i])) {
        unset($this->computedPropertyCache[$i]);
      }
    });
  }

  public function getQueryString() {
    $componentQueryString = \method_exists($this, 'queryString')
      ? $this->queryString()
      : $this->queryString;

    return \collect(\class_uses_recursive($class = static::class))
      ->map(function ($trait) use ($class) {
        $member = 'queryString' . \class_basename($trait);

        if (\method_exists($class, $member)) {
          return $this->{$member}();
        }

        if (\property_exists($class, $member)) {
          return $this->{$member};
        }

        return [];
      })
      ->values()
      ->mapWithKeys(function ($value) {
        return $value;
      })
      ->merge($componentQueryString)
      ->toArray();
  }

  public function setErrorBag($bag): MessageBag {
    return $this->errorBag = $bag instanceof MessageBag
      ? $bag
      : new MessageBag($bag);
  }

  public function bootIfNotBooted(): void {
    if (\method_exists($this, $method = 'boot')) {
      \call_user_func([$this, $method]);
    }
  }

  public function initializeTraits(): void {

    foreach (\class_uses_recursive($class = static::class) as $trait) {
      if (\method_exists($class, $method = 'initialize' . \class_basename($trait))) {
        $this->{$method}();
      }
    }
  }

  public function getWireCache(): array {
    return $this->wireCache;
  }

  protected function setWireCache($cache): void {
    $this->wireCache['#cache'] = $cache;
  }

}
