<?php

namespace Drupal\wire;

class FileUploadConfiguration {

  public static function path($path = '') {
    $directory = static::directory();
    $path = static::normalizeRelativePath($path);

    return $directory . ($path ? DIRECTORY_SEPARATOR : '') . $path;
  }

  public static function directory() {
    return DIRECTORY_SEPARATOR .
      static::normalizeRelativePath(
        \Drupal::service('file_system')->getTempDirectory() .
        DIRECTORY_SEPARATOR .
        'wire-file'
      );
  }

  /**
   * Normalize relative directories in a path.
   *
   * Taken from \League\Flysystem\Util
   *
   * @param string $path
   *
   * @return string
   * @throws \Exception
   */
  public static function normalizeRelativePath(string $path): string {
    $path = str_replace('\\', '/', $path);
    $path = static::removeFunkyWhiteSpace($path);
    $parts = [];

    foreach (explode('/', $path) as $part) {
      switch ($part) {
        case '':
        case '.':
          break;

        case '..':
          if (empty($parts)) {
            throw new \Exception(
              'Path is outside of the defined root, path: [' . $path . ']'
            );
          }
          array_pop($parts);
          break;

        default:
          $parts[] = $part;
          break;
      }
    }

    return implode('/', $parts);
  }

  /**
   * Rejects unprintable characters and invalid unicode characters.
   *
   * @param string $path
   *
   * @return string $path
   * @throws \Exception
   */
  protected static function removeFunkyWhiteSpace($path): string {
    if (preg_match('#\p{C}+#u', $path)) {
      throw new \Exception($path);
    }

    return $path;
  }

  public static function maxUploadTime(): int {
    return 5;
  }

}
