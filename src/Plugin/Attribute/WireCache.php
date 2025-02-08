<?php
declare(strict_types=1);

namespace Drupal\wire\Plugin\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class WireCache {
  public function __construct(
    public array $contexts = [],
    public array $tags = [],
    public int $maxAge = 0
  ) {}
}
