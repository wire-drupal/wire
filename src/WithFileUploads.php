<?php

namespace Drupal\wire;

use Drupal\Core\Url;
use Drupal\wire\Exceptions\ValidationException;

trait WithFileUploads {

  public function startUpload($name, $fileInfo, $isMultiple) {
    $uploadUrl = Url::fromRoute('wire.upload-file')->toString();
    $this->emit('upload:generatedSignedUrl', $name, $uploadUrl)->self();
  }

  public function finishUpload($name, $tmpPath, $isMultiple): void {
    $this->cleanupOldUploads();

    if ($isMultiple) {
      $file = \collect($tmpPath)->map(function ($i) {
        return TemporaryUploadedFile::createFromWire($i);
      })->toArray();
      $this->emit('upload:finished', $name, \collect($file)->map->getFilename()->toArray())->self();
    }
    else {
      $file = TemporaryUploadedFile::createFromWire($tmpPath[0]);
      $this->emit('upload:finished', $name, [$file->getFilename()])->self();

      // If the property is an array, but the upload ISN'T set to "multiple"
      // then APPEND the upload to the array, rather than replacing it.
      if (\is_array($value = $this->getPropertyValue($name))) {
        $file = \array_merge($value, [$file]);
      }
    }

    $this->syncInput($name, $file);
  }

  public function uploadErrored($name, $errorsInJson, $isMultiple) {
    $this->emit('upload:errored', $name)->self();

    if (\is_null($errorsInJson)) {
      $message = "The {$name} failed to upload.";

      throw new ValidationException($message);
    }

    $errorsInJson = $isMultiple
      ? \str_ireplace('files', $name, $errorsInJson)
      : \str_ireplace('files.0', $name, $errorsInJson);

    $errors = \json_decode($errorsInJson, TRUE)['errors'];

    throw new ValidationException(\implode(PHP_EOL, $errors));
  }

  public function removeUpload($name, $tmpFilename): void {
    $uploads = $this->getPropertyValue($name);

    if (\is_array($uploads) && isset($uploads[0]) && $uploads[0] instanceof TemporaryUploadedFile) {
      $this->emit('upload:removed', $name, $tmpFilename)->self();

      $this->syncInput($name, \array_values(\array_filter($uploads, function ($upload) use ($tmpFilename) {
        if ($upload->getFilename() === $tmpFilename) {
          $upload->delete();
          return FALSE;
        }

        return TRUE;
      })));
    }
    elseif ($uploads instanceof TemporaryUploadedFile && $uploads->getFilename() === $tmpFilename) {
      $uploads->delete();

      $this->emit('upload:removed', $name, $tmpFilename)->self();

      $this->syncInput($name, NULL);
    }
  }

  protected function cleanupOldUploads() {
    // @todo: To do this we need to implement our own upload destination and check by timestamp.
  }


}
