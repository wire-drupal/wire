<?php

namespace Drupal\wire\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\wire\LifecycleManager;
use Drupal\wire\Wire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class HttpConnectionHandler
 *
 * @package Drupal\wire\Controller
 */
class HttpConnectionHandler implements ContainerInjectionInterface {

  protected ?Request $currentRequest;

  public function __construct(
    protected RequestStack                  $requestStack,
    protected SessionConfigurationInterface $sessionConfiguration,
    protected CsrfTokenGenerator            $csrfToken) {
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('session_configuration'),
      $container->get('csrf_token')
    );
  }

  public function handle() {

    $payload = Wire::getPayloadFromRequest($this->currentRequest);

    if (!$this->hasSessionAndHasValidCsrfToken($payload)) {
      throw new AccessDeniedHttpException('Bad token');
    }

    return new JsonResponse(
      LifecycleManager::fromSubsequentRequest($payload)
        ->boot()
        ->hydrate()
        ->renderToView()
        ->dehydrate()
        ->toSubsequentResponse()
    );
  }

  private function hasSessionAndHasValidCsrfToken($payload): bool {

    if (!$this->sessionConfiguration->hasSession($this->currentRequest)) {
      return TRUE;
    }

    $request = new Request();
    $request = $request->create($this->currentRequest->headers->get('referer'));

    return $this->csrfToken->validate(
      $payload['fingerprint']['csrfToken'],
      $request->getPathInfo() ?? ''
    );
  }

}
