<?php

namespace Drupal\wire\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\MetadataBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class AnonymousCsrfTokenGenerator.
 */
class AnonymousCsrfTokenGenerator extends CsrfTokenGenerator {

  protected SessionInterface $session;

  public function __construct(PrivateKey $private_key, MetadataBag $session_metadata, SessionInterface $session) {
    parent::__construct($private_key, $session_metadata);
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   *
   * Set a value in the session for anonymous users so that the session is persistent.
   */
  public function get($value = ''): string {
    if ($this->session->isStarted() === FALSE) {
      $this->session->set('anon_session_id', Crypt::randomBytesBase64());
    }
    return parent::get($value);
  }

}
