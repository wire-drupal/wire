<?php

namespace Drupal\wire\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Site\Settings;
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

  protected Request $currentRequest;

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

    $isAnon = $this->account->isAnonymous();

    // Only check for anonymous users if it's been explicitly told so.
    // @see: https://www.drupal.org/node/2319205
    $anonService = Settings::get('wire_anon_csrf_service', FALSE);
    $shouldCheck = $anonService && $isAnon;

    if ($anonService && !\Drupal::hasService($anonService)) {
      // Log the problem but continue and let the request fail.
      watchdog_exception('wire', new \Exception('Defined "wire_anon_csrf_service" in Settings does not exists'));
    }

    // Always check for authenticated users.
    $shouldCheck = $shouldCheck || !$isAnon;

    if (!$shouldCheck) {
      return TRUE;
    }

    // At this point, CSRF is must for any user.
    $csrfTokenInHeader = $this->currentRequest->headers->get('W-CSRF-TOKEN');
    if (empty($csrfTokenInHeader)) {
      return FALSE;
    }

    $request = new Request();
    $request = $request->create($this->currentRequest->headers->get('referer'));

    if (!$isAnon) {
      return $this->csrfToken->validate(
        $csrfTokenInHeader,
        $request->getPathInfo() ?? ''
      );
    }
    else {
      return \Drupal::service($anonService)->validate(
        $csrfTokenInHeader,
        $request->getPathInfo() ?? ''
      );
    }
  }

}
