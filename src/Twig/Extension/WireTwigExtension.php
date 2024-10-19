<?php

namespace Drupal\wire\Twig\Extension;

use Drupal\wire\WireManager;
use Illuminate\Support\MessageBag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class WireTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('wire', [$this, 'wire'], ['is_safe' => ['html']]),

      new TwigFunction('wireThis', [$this, 'wireThis',], ['is_safe' => ['html'], 'needs_context' => TRUE]),

      new TwigFunction('wireError', [$this, 'wireError',], ['needs_context' => TRUE]),

      new TwigFunction('wireErrors', [$this, 'wireErrors',], ['needs_context' => TRUE]),
    ];
  }

  public function getFilters(): array {
    return [
      new TwigFilter('wireJs', [$this, 'wireJs'], ['is_safe' => ['html']]),
    ];
  }

  public function wire($resource, array $params = []): ?string {
    return WireManager::mount($resource, $params)?->html();
  }

  public function wireJs($data): ?string {
    if (\is_object($data) || \is_array($data)) {
      return "JSON.parse(atob('" . \base64_encode(\json_encode($data)) . "'))";
    }
    elseif (\is_string($data)) {
      return "'" . \str_replace("'", "\'", $data) . "'";
    }
    else {
      return \json_encode($data);
    }
  }

  public function wireThis(array $context): ?string {
    return "window.wire.find('" . $context['__wire_id'] . "')";
  }

  public function wireError(array $context, $key): ?string {
    $errors = $this->wireErrors($context);
    return $errors->has($key) ? $errors->first($key) : '';
  }

  public function wireErrors(array $context): MessageBag {
    return $context['__wire_errors'];
    // To use methods of Message bag we will need to allow the class and methods
    // as per Drupal\Core\Template\TwigSandboxPolicy
  }

}
