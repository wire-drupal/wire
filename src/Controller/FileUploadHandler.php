<?php

namespace Drupal\wire\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\wire\FileUploadConfiguration;
use Drupal\wire\TemporaryUploadedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class FileUploadHandler implements ContainerInjectionInterface {

  public function __construct(protected RequestStack $requestStack) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  public function handle() {
    $files = $this->requestStack->getCurrentRequest()->files->all()['files'] ?? [];

    $filePaths = \collect($files)->map(function ($file) {
      $filename = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($file);
      /** @var \Symfony\Component\HttpFoundation\File\File $file */
      $file->move(FileUploadConfiguration::directory(), $filename);
      return $filename;
    });

    return new JsonResponse(['paths' => $filePaths]);
  }

}
