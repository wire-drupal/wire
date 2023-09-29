<?php

namespace Drupal\wire\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileSystem;
use Drupal\wire\FileUploadConfiguration;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

class FilePreviewHandler implements ContainerInjectionInterface {

  use CanPretendToBeAFile;

  public function __construct(
    protected FileSystem               $fileSystem,
    protected MimeTypeGuesserInterface $mimeTypeGuesser
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('file_system'),
      $container->get('file.mime_type.guesser')
    );
  }

  public function handle($filename) {
    $filePath = FileUploadConfiguration::path($filename);
    return $this->pretendResponseIsFile(
      $filePath,
      $this->mimeTypeGuesser->guessMimeType($filePath)
    );
  }

}
