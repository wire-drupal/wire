<?php

namespace Drupal\wire;

use Drupal\Core\File\FileExists;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class TemporaryUploadedFile extends SymfonyUploadedFile {

  protected string $path;

  public function __construct($path) {
    $this->path = FileUploadConfiguration::path($path);

    $tmpFile = \tmpfile();
    parent::__construct(\stream_get_meta_data($tmpFile)['uri'], $this->path);
  }

  public function getPath(): string {
    return $this->path;
  }

  public function isValid(): bool {
    return TRUE;
  }

  public function store($destination = NULL, $filename = NULL, $replace = FileExists::Rename) {
    $fileSystem = \Drupal::service('file_system');
    if (!$destination) {
      $destination = \Drupal::config('system.file')->get('default_scheme') . '://wire';
      @$fileSystem->mkdir($destination, NULL, TRUE);
    }

    $newPath = $destination . '/' . ($filename ?? $this->getFilename());

    return $fileSystem->move($this->path, $newPath, $replace);
  }

  public function getMimeType(): string {
    $mimeType = \Drupal::service('file.mime_type.guesser')->guessMimeType($this->path) ?: 'text/plain';
    return $mimeType === 'image/svg' ? 'image/svg+xml' : $mimeType;
  }

  public function getFilename(): string {
    return $this->getName($this->path);
  }

  public function getClientOriginalName(): string {
    return $this->extractOriginalNameFromFilePath($this->path);
  }

  public function temporaryUrl():string {

    if (!$this->isPreviewable()) {
      // @todo: Throw an exception?(when we have a proper validation handler)
      return '';
    }

    return Url::fromRoute('wire.preview-file', ['filename' => $this->getFilename()])->toString();
  }

  public function isPreviewable(): bool {
    $supportedPreviewTypes = [
      'png',
      'gif',
      'bmp',
      'svg',
      'wav',
      'mp4',
      'mov',
      'avi',
      'wmv',
      'mp3',
      'm4a',
      'jpg',
      'jpeg',
      'mpga',
      'webp',
      'wma',
    ];

    return \in_array($this->guessExtension(), $supportedPreviewTypes);
  }

  public function delete() {
    return \Drupal::service('file_system')->delete($this->path);
  }

  public static function generateHashNameWithOriginalNameEmbedded($file): string {
    $hash = \str()->random(30);
    $meta = \str('-meta' . \base64_encode($file->getClientOriginalName()) . '-')->replace('/', '_');
    $extension = '.' . $file->guessExtension();

    return $hash . $meta . $extension;
  }

  public function extractOriginalNameFromFilePath($path): false|string {
    return \base64_decode(\head(\explode('-', \last(\explode('-meta', \str($path)->replace('_', '/'))))));
  }

  public static function createFromWire($filePath) {
    return new static($filePath);
  }

  public static function canUnserialize($subject) {
    if (\is_string($subject)) {
      return (string) \str($subject)->startsWith(['wire-file:', 'wire-files:']);
    }

    if (\is_array($subject)) {
      return \collect($subject)->contains(function ($value) {
        return static::canUnserialize($value);
      });
    }

    return FALSE;
  }

  public static function unserializeFromWireRequest($subject) {
    if (\is_string($subject)) {
      if (\str($subject)->startsWith('wire-file:')) {
        return static::createFromWire(\str($subject)->after('wire-file:'));
      }

      if (\str($subject)->startsWith('wire-files:')) {
        $paths = \json_decode(\str($subject)->after('wire-files:'), TRUE);

        return \collect($paths)->map(function ($path) {
          return static::createFromWire($path);
        })->toArray();
      }
    }

    if (\is_array($subject)) {
      foreach ($subject as $key => $value) {
        $subject[$key] = static::unserializeFromWireRequest($value);
      }
    }

    return $subject;
  }

  public function serializeForWireResponse(): string {
    return 'wire-file:' . $this->getFilename();
  }

  public static function serializeMultipleForWireResponse($files): string {
    return 'wire-files:' . \json_encode(\collect($files)->map->getFilename());
  }

}
