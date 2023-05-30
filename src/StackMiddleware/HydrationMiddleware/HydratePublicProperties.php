<?php

namespace Drupal\wire\StackMiddleware\HydrationMiddleware;

use Drupal\wire\Exceptions\PublicPropertyTypeNotAllowedException;
use Drupal\wire\Wireable;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use ReflectionProperty;
use DateTimeImmutable;
use DateTime;
use DateTimeInterface;
use Normalizer;

class HydratePublicProperties implements HydrationMiddleware {

  public static function hydrate($instance, $request) {
    $publicProperties = $request->memo['data'] ?? [];

    $dates = data_get($request, 'memo.dataMeta.dates', []);
    $collections = data_get($request, 'memo.dataMeta.collections', []);
    $stringables = data_get($request, 'memo.dataMeta.stringables', []);
    $wireables = data_get($request, 'memo.dataMeta.wireables', []);
    $enums = data_get($request, 'memo.dataMeta.enums', []);

    foreach ($publicProperties as $property => $value) {

      if ($type = data_get($dates, $property)) {
        $types = [
          'native' => DateTime::class,
          'nativeImmutable' => DateTimeImmutable::class,
        ];

        data_set($instance, $property, new $types[$type]($value));
      }
      elseif (in_array($property, $collections)) {
        data_set($instance, $property, collect($value));
      }
      elseif ($class = data_get($enums, $property)) {
        data_set($instance, $property, $class::from($value));
      }
      elseif (in_array($property, $stringables)) {
        data_set($instance, $property, new Stringable($value));
      }
      elseif (in_array($property, $wireables)) {
        /** @var \Drupal\wire\Wireable $type */
        $type = (new \ReflectionClass($instance))
          ?->getProperty($property)
          ?->getType()
          ?->getName();

        throw_if(is_null($type), new \Exception(sprintf('Property "%s" on %s is missing it\'s Wireable typed hinted property', $property, get_class($instance))));

        data_set($instance, $property, $type::fromWire($value));
      }
      else {
        // Do not use reflection for virtual component properties.
        if (property_exists($instance, $property) && (new ReflectionProperty($instance, $property))->getType()) {
          is_null($value) || $instance->$property = $value;
        }
        else {
          $instance->$property = $value;
        }
      }
    }
  }

  public static function dehydrate($instance, $response) {
    $publicData = $instance->getPublicPropertiesDefinedBySubClass();

    data_set($response, 'memo.data', []);
    data_set($response, 'memo.dataMeta', []);

    array_walk($publicData, function ($value, $key) use ($instance, $response) {
      if (
        // The value is a supported type, set it in the data, if not, throw an exception for the user.
        is_bool($value) || is_null($value) || is_numeric($value)
      ) {
        data_set($response, 'memo.data.' . $key, $value);
      }
      elseif (is_array($value)) {
        // Normalize data so that Safari handles special characters properly without throwing a checksum exception.
        data_set($response, 'memo.data.' . $key, static::normalizeArray($value));
      }
      elseif (is_string($value)) {
        // Normalize data so that Safari handles special characters properly without throwing a checksum exception.
        data_set($response, 'memo.data.' . $key, Normalizer::normalize($value));
      }
      elseif ($value instanceof Wireable) {
        $response->memo['dataMeta']['wireables'][] = $key;
        data_set($response, 'memo.data.' . $key, $value->toWire());
      }
      else {
        if ($value instanceof Collection) {
          $response->memo['dataMeta']['collections'][] = $key;
          // Normalize data so that Safari handles special characters properly without throwing a checksum exception.
          data_set($response, 'memo.data.' . $key, static::normalizeCollection($value)->toArray());
        }
        elseif ($value instanceof DateTimeInterface) {
          if ($value instanceof DateTimeImmutable) {
            $response->memo['dataMeta']['dates'][$key] = 'nativeImmutable';
          }
          else {
            $response->memo['dataMeta']['dates'][$key] = 'native';
          }

          data_set($response, 'memo.data.' . $key, $value->format(\DateTimeInterface::RFC7231));
        }
        elseif ($value instanceof Stringable) {
          $response->memo['dataMeta']['stringables'][] = $key;

          data_set($response, 'memo.data.' . $key, $value->__toString());
        }
        elseif (is_subclass_of($value, 'BackedEnum')) {
          $response->memo['dataMeta']['enums'][$key] = get_class($value);

          data_set($response, 'memo.data.' . $key, $value->value);
        }
        else {
          throw new PublicPropertyTypeNotAllowedException($instance->id, $key, $value);
        }
      }
    });
  }

  protected static function normalizeArray($value) {
    return array_map(function ($item) {
      if (is_string($item)) {
        return Normalizer::normalize($item);
      }

      if (is_array($item)) {
        return static::normalizeArray($item);
      }

      if ($item instanceof Collection) {
        return static::normalizeCollection($item);
      }

      return $item;
    }, $value);
  }

  protected static function normalizeCollection($value) {
    return $value->map(function ($item) {
      if (is_string($item)) {
        return Normalizer::normalize($item);
      }

      if (is_array($item)) {
        return static::normalizeArray($item);
      }

      if ($item instanceof Collection) {
        return static::normalizeCollection($item);
      }

      return $item;
    });
  }

}
