<?php

namespace Drupal\wire\Twig\Extension;

use Drupal\wire\WireManager;
use Illuminate\Support\MessageBag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WireTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('wire', [
        $this,
        'wire',
      ], ['is_safe' => ['html']]),
      new TwigFunction('wireError', [
        $this,
        'wireError',
      ], ['needs_context' => TRUE]),
      new TwigFunction('wireErrors', [
        $this,
        'wireErrors',
      ], ['needs_context' => TRUE]),
    ];
  }

  public function wire($resource, array $params = []): ?string {
    return WireManager::mount($resource, $params)->html();
  }

  public function wireError(array $context, $key): ?string {
    $errors = $this->wireErrors($context);
    return $errors->has($key) ? $errors->first($key) : '';
  }

  public function wireErrors(array $context): MessageBag {
    return $context['__wire_errors'];
    // To use methods of Message bug we will need to allow the class and methods
    // as per Drupal\Core\Template\TwigSandboxPolicy
  }

}
