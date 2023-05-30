<?php

namespace Drupal\wire;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;

class WireComponentsRegistry {

  use LoggerChannelTrait;

  protected array $registry = [];

  protected readonly ActiveTheme $activeTheme;

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected ThemeManagerInterface  $themeManager,
    protected CacheBackendInterface  $cache,
    protected FileSystemInterface    $fileSystem,
    private readonly string          $appRoot) {
    $this->activeTheme = $this->themeManager->getActiveTheme();
  }

  public function getTemplate(string $name): ?string {

    $themeName = $this->activeTheme->getName();
    if (!isset($this->registry[$themeName])) {
      $this->load($themeName);
    }

    return $this->registry[$themeName][$name] ?? NULL;
  }

  protected function load(string $themeName): void {
    if ($cache = $this->cache->get('wire_components:registry:' . $themeName)) {
      $this->registry[$themeName] = $cache->data;
    }
    else {
      $this->registry[$themeName] = [];
      foreach ($this->getScanDirectories($themeName) as $directoryPath) {
        try {
          $files = $this->fileSystem->scanDirectory($directoryPath, '/\.(twig)$/');

        } catch (NotRegularDirectoryException $exception) {
          $this->getLogger('wire')->warning((sprintf('"@%s" is not a directory.', $directoryPath)));
          $files = [];
        }

        ksort($files);
        foreach ($files as $filePath => $file) {
          $expectedTplName = str_replace('.html.twig', '', $file->filename);
          $this->registry[$themeName][$expectedTplName] = $filePath;
        }
      }

      if ($this->moduleHandler->isLoaded()) {
        $this->cache->set(
          'wire_components:registry:' . $themeName,
          $this->registry[$themeName],
          Cache::PERMANENT,
          ['theme_registry']
        );
      }
    }
  }

  private function getScanDirectories(string $themeName): array {

    if ($cached = $this->cache->get('wire_components:directories:' . $themeName)) {
      return $cached->data;
    }

    $baseThemePaths = array_map(
      static fn(Extension $extension) => $extension->getPath(),
      $this->activeTheme->getBaseThemeExtensions()
    );

    $appRoot = $this->appRoot;
    $extensionDirectories = [
      ...$this->moduleHandler->getModuleDirectories(),
      ...array_map(
        static fn(string $path) => $appRoot . '/' . $path,
        [
          ...$baseThemePaths,
          $this->activeTheme->getPath(),
        ]
      ),
    ];

    $reducedExtensionDirectories = array_filter(array_map(function ($path) {
      $tplStorageDir = sprintf(
        '%s%s%s%s%s',
        rtrim($path, DIRECTORY_SEPARATOR),
        DIRECTORY_SEPARATOR,
        'templates',
        DIRECTORY_SEPARATOR,
        'wire',
      );

      return !str_contains($tplStorageDir, '/core') && is_dir($tplStorageDir)
        ? $tplStorageDir
        : NULL;
    }, $extensionDirectories));

    if ($this->moduleHandler->isLoaded()) {
      $this->cache->set(
        'wire_components:directories:' . $themeName,
        $reducedExtensionDirectories,
        Cache::PERMANENT,
        ['theme_registry']
      );
    }

    return $reducedExtensionDirectories;
  }

}
