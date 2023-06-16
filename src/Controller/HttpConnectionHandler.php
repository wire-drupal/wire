<?php

namespace Drupal\wire\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxy;
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
    protected RequestStack       $requestStack,
    protected AccountProxy       $account,
    protected CsrfTokenGenerator $csrfToken) {
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('csrf_token')
    );
  }

  public function handle(): JsonResponse {
    $this->currentRequest = $this->requestStack->getCurrentRequest();

    if (!$this->hasAndItIsValidCsrfToken()) {
      throw new AccessDeniedHttpException('Bad token');
    }

    $payload = Wire::getPayloadFromRequest($this->currentRequest);

    return new JsonResponse(
      LifecycleManager::fromSubsequentRequest($payload)
        ->boot()
        ->hydrate()
        ->renderToView()
        ->dehydrate()
        ->toSubsequentResponse()
    );
  }

  private function hasAndItIsValidCsrfToken(): bool {

    // Only check for authenticated users.
    // @see: https://www.drupal.org/node/2319205
    if ($this->account->isAnonymous()) {
      return TRUE;
    }

    $csrfTokenInHeader = $this->currentRequest->headers->get('W-CSRF-TOKEN');
    if (empty($csrfTokenInHeader)) {
      return FALSE;
    }

    $request = new Request();
    $request = $request->create($this->currentRequest->headers->get('referer'));

    return $this->csrfToken->validate(
      $csrfTokenInHeader,
      $request->getPathInfo() ?? ''
    );

  }

}
