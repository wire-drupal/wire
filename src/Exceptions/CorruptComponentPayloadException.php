<?php

namespace Drupal\wire\Exceptions;

class CorruptComponentPayloadException extends \Exception {

  public function __construct($component) {
    parent::__construct(
      "Wire encountered corrupt data when trying to hydrate the [{$component}] component. \n" .
      "Ensure that the [name, id, data] of the Wire component wasn't tampered with between requests."
    );
  }

}
