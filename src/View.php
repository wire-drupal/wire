<?php

namespace Drupal\wire;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Template\TwigEnvironment;

class View {

  protected static string $view;

  protected static array $data;

  protected static ?WireComponentsRegistry $componentsRegistry;

  protected static ?ExtensionPathResolver $extensionPathResolver;

  protected static ?TwigEnvironment $twigEnvironment;

  public static function fromTpl($tpl, $data = []): static {

    self::$view = self::getViewFromTpl($tpl);
    self::$data = $data;

    return new static();
  }

  public static function fromString($rawView, $data = []): static {

    self::$view = $rawView;
    self::$data = $data;

    return new static();
  }

  public function render($mergeData = []): string {
    $view = self::$view
      . PHP_EOL . '{{ __wire_cache }}'
      . PHP_EOL . '{% if __wire_assets %}'
      . PHP_EOL . '{{ attach_library("wire/init") }}'
      . PHP_EOL . '{% endif %}';
    return $this->twigEnv()->renderInline(
      $view, self::$data + $mergeData
    );
  }

  private static function getViewFromTpl(string $tplPath): string {

    // Strict template loader.
    if ($tplPath[0] === '@') {
      $tplParts = explode('/', $tplPath);
      $themeOrModulePath = self::extensionPathResolver()->getPath(str_replace('@', '', $tplParts[0]), $tplParts[1]);
      $tplParts[0] = \Drupal::root();
      $tplParts[1] = $themeOrModulePath;
      $templateToUse = implode('/', $tplParts);
    }
    // From registry.
    else {
      $templateToUse = self::componentsRegistry()->getTemplate($tplPath);
      throw_if(!$templateToUse, new \Exception('Template not found: ' . $tplPath));
    }

    throw_if(!file_exists($templateToUse), new \Exception('Template file does not exist: ' . $tplPath));
    return file_get_contents($templateToUse);
  }

  private static function extensionPathResolver(): ExtensionPathResolver {
    if (!isset(self::$extensionPathResolver)) {
      self::$extensionPathResolver = \Drupal::getContainer()->get('extension.path.resolver');
    }
    return self::$extensionPathResolver;
  }

  private static function componentsRegistry(): WireComponentsRegistry {
    if (!isset(self::$componentsRegistry)) {
      self::$componentsRegistry = \Drupal::getContainer()->get('wire.components.registry');
    }
    return self::$componentsRegistry;
  }

  public static function twigEnv() {
    if (!isset(self::$twigEnvironment)) {
      self::$twigEnvironment = \Drupal::service('twig');
    }
    return self::$twigEnvironment;
  }

}
