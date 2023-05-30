<?php

namespace Drupal\wire\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\wire\WireManager;

/**
 * Provides a Wire component render element.
 *
 * Properties:
 * - #id: The machine name of the wire component.
 * - #context: Variables to be passed to wire component.
 *
 * Usage Example:
 *
 * @code
 * $build['wire_component'] = [
 *   '#type' => 'wire',
 *   '#id' => 'counter',
 *   '#context' => ['count' => 0],
 * ];
 * @endcode
 *
 * @RenderElement("wire")
 */
class WireElement extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = static::class;
    return [
      '#pre_render' => [
        [$class, 'preRenderComponent'],
      ],
      '#id' => '',
      '#context' => [],
    ];
  }

  /**
   * Simply converts wire as inline_template for convenience.
   */
  public static function preRenderComponent(array $element): array {

    // Soft fail if wire component id is not set.
    if (empty($element['#id'])) {
      return tap($element, function (&$element) {
        $element['inline-template'] = [
          '#type' => 'inline_template',
          '#template' => '<span style="visibility:hidden;">Wire Component "#id" is not set </span>',
        ];
      });
    }

    $context = $element['#context'] ?? [];
    $element['inline-template'] = [
      '#type' => 'inline_template',
      '#template' => WireManager::mount($element['#id'], $context)->html(),
    ];
    return $element;
  }

}
