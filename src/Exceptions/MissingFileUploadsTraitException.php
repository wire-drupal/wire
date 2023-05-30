<?php

namespace Drupal\wire\Exceptions;

class MissingFileUploadsTraitException extends \Exception {

  public function __construct($component) {
    parent::__construct(
      "Cannot handle file upload without [Drupal\wire\WithFileUploads] trait on the [{$component}] component class."
    );
  }

}
