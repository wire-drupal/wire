<?php

namespace Drupal\wire\Features;

use Drupal\wire\Response;
use Drupal\wire\Wire;

use Drupal\wire\WireComponentInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SupportBrowserHistory {

  protected $mergedQueryParamsFromDehydratedComponents;

  static function init(): static {
    return new static;
  }

  function __construct() {
    Wire::listen('component.hydrate.initial', function ($component) {
      if (!$this->getQueryParamsFromComponentProperties($component)->keys()) {
        return;
      }

      $queryParams = \Drupal::request()->query->all();

      foreach ($component->getQueryString() ?? [] as $property => $options) {
        if (!is_array($options)) {
          $property = $options;
        }

        $fromQueryString = Arr::get($queryParams, $options['as'] ?? $property);
        if ($fromQueryString === NULL) {
          continue;
        }

        // Clean-up possible dirty query argument(e.g: ?page=1%3Fpage%3D1).
        if (!is_array($fromQueryString) && !empty($fromQueryString)) {
          parse_str($fromQueryString, $fromQueryStringArray);
          $fromQueryString = Wire::str(key($fromQueryStringArray))->before('?');
        }

        $decoded = is_array($fromQueryString)
          ? json_decode(json_encode($fromQueryString), TRUE)
          : json_decode($fromQueryString, TRUE);

        $component->$property = $decoded === NULL ? $fromQueryString : $decoded;
      }
    });

    Wire::listen('component.dehydrate.initial', function (WireComponentInterface $component, Response $response) {

      $request = \Drupal::request();
      if (($referer = $request->headers->get('referer')) && $request->headers->get('x-wire')) {
        $this->getPathFromReferer($referer, $component, $response);
      }
      else {
        if (!$this->shouldSendPath($component)) {
          return;
        }

        $queryParams = $this->mergeComponentPropertiesWithExistingQueryParamsFromOtherComponentsAndTheRequest($component);
        $response->effects['path'] = Wire::str(\Drupal::request()->getUri())
            ->before('?') . $this->stringifyQueryParams($queryParams);
      }
    });

    Wire::listen('component.dehydrate.subsequent', function (WireComponentInterface $component, Response $response) {
      if (!$referer = \Drupal::request()->headers->get('referer')) {
        return;
      }

      $this->getPathFromReferer($referer, $component, $response);
    });

    Wire::listen('flush-state', function () {
      $this->mergedQueryParamsFromDehydratedComponents = [];
    });
  }

  protected function getPathFromReferer($referer, $component, $response): void {
    if (!$this->shouldSendPath($component)) {
      return;
    }

    $queryParams = $this->mergeComponentPropertiesWithExistingQueryParamsFromOtherComponentsAndTheRequest($component);
    $path = $this->buildPathFromReferer($referer, $queryParams);

    if ($referer !== $path) {
      $response->effects['path'] = $path;
    }
  }

  protected function shouldSendPath($component): bool {
    // If the component is setting $queryString params.
    return !$this->getQueryParamsFromComponentProperties($component)->isEmpty();
  }

  protected function getExistingQueryParams() {
    return Wire::isDefinitelyWireRequest()
      ? $this->getQueryParamsFromRefererHeader()
      : \Drupal::request()->query->all();
  }

  public function getQueryParamsFromRefererHeader(): array {
    if (empty($referer = \Drupal::request()->headers->get('referer'))) {
      return [];
    }

    parse_str((string) parse_url($referer, PHP_URL_QUERY), $refererQueryString);

    return $refererQueryString;
  }

  protected function buildPathFromReferer($referer, $queryParams): string {
    return Wire::str($referer)->before('?') . $this->stringifyQueryParams($queryParams);
  }

  protected function mergeComponentPropertiesWithExistingQueryParamsFromOtherComponentsAndTheRequest($component) {
    if (!$this->mergedQueryParamsFromDehydratedComponents) {
      $this->mergedQueryParamsFromDehydratedComponents = collect($this->getExistingQueryParams());
    }

    $excepts = $this->getExceptsFromComponent($component);

    $this->mergedQueryParamsFromDehydratedComponents = collect(\Drupal::request()->query->all())
      ->merge($this->mergedQueryParamsFromDehydratedComponents)
      ->merge($this->getQueryParamsFromComponentProperties($component))
      ->reject(function ($value, $key) use ($excepts) {
        return isset($excepts[$key]) && $excepts[$key] === $value;
      })
      ->map(function ($property) {
        return is_bool($property) ? json_encode($property) : $property;
      });

    return $this->mergedQueryParamsFromDehydratedComponents;
  }

  protected function getExceptsFromComponent($component) {
    return collect($component->getQueryString())
      ->filter(function ($value) {
        return isset($value['except']);
      })
      ->mapWithKeys(function ($value, $key) {
        $key = $value['as'] ?? $key;
        return [$key => $value['except']];
      });
  }

  protected function getQueryParamsFromComponentProperties($component): Collection {
    return collect($component->getQueryString())
      ->mapWithKeys(function ($value, $key) use ($component) {
        $key = is_string($key) ? $key : $value;
        $alias = $value['as'] ?? $key;

        return [$alias => $component->{$key}];
      });
  }

  protected function stringifyQueryParams($queryParams): string {
    if ($queryParams->isEmpty()) {
      return '';
    }

    return '?' . http_build_query($queryParams->toArray(), '', '&', PHP_QUERY_RFC1738);
  }

}
