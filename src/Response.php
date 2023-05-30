<?php

namespace Drupal\wire;

class Response {

  public $fingerprint;

  public $effects;

  public $cache;

  public $memo;

  public bool $isStateless = FALSE;

  public function __construct(public Request $request) {
    $this->fingerprint = $request->fingerprint;
    $this->memo = $request->memo;
    $this->effects = [];
  }

  public static function fromRequest($request): static {
    return new static($request);
  }

  public function id() {
    return $this->fingerprint['id'];
  }

  public function name() {
    return $this->fingerprint['name'];
  }

  public function toInitialResponse() {
    return tap($this)->embedIdInHtml();
  }

  public function embedThyselfInHtml(): void {
    if (!$html = $this->effects['html'] ?? NULL || $this->isStateless) {
      return;
    }

    $this->effects['html'] = (new AddAttributesToRootTagOfHtml)($html, [
      'initial-data' => $this->toArrayWithoutHtml(),
    ]);
  }

  public function embedIdInHtml(): void {
    if (!$html = $this->effects['html'] ?? NULL || $this->isStateless) {
      return;
    }

    $this->effects['html'] = (new AddAttributesToRootTagOfHtml)($html, [
      'id' => $this->fingerprint['id'],
    ]);
  }

  public function toArrayWithoutHtml(): array {
    return [
      'fingerprint' => $this->fingerprint,
      'effects' => array_diff_key($this->effects, ['html' => NULL]),
      'serverMemo' => $this->memo,
    ];
  }

  public function toSubsequentResponse(): array {
    $this->embedIdInHtml();

    $requestMemo = $this->request->memo;
    $responseMemo = $this->memo;
    $dirtyMemo = [];

    // Only send along the memos that have changed to not bloat the payload.
    foreach ($responseMemo as $key => $newValue) {
      // If the memo key is not in the request, add it.
      if (!isset($requestMemo[$key])) {
        $dirtyMemo[$key] = $newValue;

        continue;
      }

      // If the memo values are the same, skip adding them.
      if ($requestMemo[$key] === $newValue) {
        continue;
      }

      $dirtyMemo[$key] = $newValue;
    }

    // If 'data' is present in the response memo, diff it one level deep.
    if (isset($dirtyMemo['data']) && isset($requestMemo['data'])) {
      foreach ($dirtyMemo['data'] as $key => $value) {
        if (!isset($requestMemo['data'][$key])) {
          continue;
        }

        if ($value === $requestMemo['data'][$key]) {
          unset($dirtyMemo['data'][$key]);
        }
      }
    }

    // Make sure any data marked as "dirty" is present in the resulting data payload.
    foreach (data_get($this, 'effects.dirty', []) as $property) {
      $property = head(explode('.', $property));

      data_set($dirtyMemo, 'data.' . $property, $responseMemo['data'][$property] ?? NULL);
    }

    return [
      'effects' => $this->effects,
      'serverMemo' => $dirtyMemo,
    ];
  }

}
