<?php

namespace Drupal\wire;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;

trait CacheTrait {

  /**
   * Cache storage.
   */
  protected array $cache = [];

  private function setCache(string $type, string|array $value): void {
    if (is_array($value) && $type === 'tags') {
      $this->cache[$type] = Cache::mergeTags($this->cache[$type] ?? [], $value);
      return;
    }

    if (is_array($value) && $type === 'contexts') {
      $this->cache[$type] = Cache::mergeContexts($this->cache[$type] ?? [], $value);
      return;
    }

    if (!is_array($value) && $type === 'max-age') {
      $maxAges = array_merge($this->cache[$type] ?? [], [$value]);
      $minMaxAge = Cache::mergeMaxAges($maxAges);
      $this->cache[$type] = is_array($minMaxAge) ? reset($minMaxAge) : $minMaxAge;
    }
  }

  public function getCache(): array {
    return $this->cache;
  }

  public function mergeCache(array $cache): void {
    $this->cache = NestedArray::mergeDeep($this->cache, $cache);
  }

}
