<?php

namespace Drupal\wire\StackMiddleware;

use Drupal\wire\Features\OptimizeRenderedDom;
use Drupal\wire\Features\SupportBootMethod;
use Drupal\wire\Features\SupportBrowserHistory;
use Drupal\wire\Features\SupportComponentTraits;
use Drupal\wire\Features\SupportEvents;
use Drupal\wire\Features\SupportFileUploads;
use Drupal\wire\Features\SupportRedirects;
use Drupal\wire\Features\SupportRootElementTracking;
use Drupal\wire\StackMiddleware\HydrationMiddleware\CallHydrationHooks;
use Drupal\wire\StackMiddleware\HydrationMiddleware\CallPropertyHydrationHooks;
use Drupal\wire\StackMiddleware\HydrationMiddleware\HashDataPropertiesForDirtyDetection;
use Drupal\wire\StackMiddleware\HydrationMiddleware\HydratePublicProperties;
use Drupal\wire\StackMiddleware\HydrationMiddleware\NormalizeComponentPropertiesForJavaScript;
use Drupal\wire\StackMiddleware\HydrationMiddleware\NormalizeServerMemoSansDataForJavaScript;
use Drupal\wire\LifecycleManager;
use Drupal\wire\StackMiddleware\HydrationMiddleware\PerformAccessCheck;
use Drupal\wire\StackMiddleware\HydrationMiddleware\PerformActionCalls;
use Drupal\wire\StackMiddleware\HydrationMiddleware\PerformDataBindingUpdates;
use Drupal\wire\StackMiddleware\HydrationMiddleware\PerformEventEmissions;
use Drupal\wire\StackMiddleware\HydrationMiddleware\RenderView;
use Drupal\wire\StackMiddleware\HydrationMiddleware\SecureHydrationWithChecksum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Symfony middleware registering needed Wire middleware.
 */
class RegisterWireMiddleware implements HttpKernelInterface {

  public function __construct(protected HttpKernelInterface $httpKernel) {
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {

    SupportEvents::init();
    OptimizeRenderedDom::init();
    SupportBootMethod::init();
    SupportRedirects::init();
    SupportFileUploads::init();
    SupportBrowserHistory::init();
    SupportComponentTraits::init();
    SupportRootElementTracking::init();

    LifecycleManager::registerHydrationMiddleware([

      /* This is the core middleware stack of Wire.
      /* The request goes through each class by the */
      /* order it is listed in this array, and is reversed on response */
      /*                                                               */
      /* ↓    Incoming Request                  Outgoing Response    ↑ */
      /* ↓                                                           ↑ */
      /* ↓    Secure Stuff                                           ↑ */
      /* ↓ */ SecureHydrationWithChecksum::class, /* --------------- ↑ */
      /* ↓ */ NormalizeServerMemoSansDataForJavaScript::class, /* -- ↑ */
      /* ↓ */ HashDataPropertiesForDirtyDetection::class, /* ------- ↑ */
      /* ↓                                                           ↑ */
      /* ↓    Hydrate Stuff                                          ↑ */
      /* ↓ */ HydratePublicProperties::class, /* ------------------- ↑ */
      /* ↓ */ CallPropertyHydrationHooks::class, /* ---------------- ↑ */
      /* ↓ */ CallHydrationHooks::class, /* ------------------------ ↑ */
      /* ↓                                                           ↑ */
      /* ↓    Update Stuff                                           ↑ */
      /* ↓ */ PerformDataBindingUpdates::class, /* ----------------- ↑ */
      /* ↓ */ PerformActionCalls::class, /* ------------------------ ↑ */
      /* ↓ */ PerformEventEmissions::class, /* --------------------- ↑ */
      /* ↓                                                           ↑ */
      /* ↓    Access checks                                          ↑ */
      /* ↓ */ PerformAccessCheck::class, /* ------------------------ ↑ */
      /* ↓                                                           ↑ */
      /* ↓    Output Stuff                                           ↑ */
      /* ↓ */ RenderView::class, /* -------------------------------- ↑ */
      /* ↓ */ NormalizeComponentPropertiesForJavaScript::class, /* - ↑ */

    ]);

    LifecycleManager::registerInitialDehydrationMiddleware([

      /* Initial Response */
      /* ↑ */ [SecureHydrationWithChecksum::class, 'dehydrate'],
      /* ↑ */ [NormalizeServerMemoSansDataForJavaScript::class, 'dehydrate'],
      /* ↑ */ [HydratePublicProperties::class, 'dehydrate'],
      /* ↑ */ [CallPropertyHydrationHooks::class, 'dehydrate'],
      /* ↑ */ [CallHydrationHooks::class, 'initialDehydrate'],
      /* ↑ */ [RenderView::class, 'dehydrate'],
      /* ↑ */ [NormalizeComponentPropertiesForJavaScript::class, 'dehydrate'],

    ]);

    LifecycleManager::registerInitialHydrationMiddleware([

      [CallHydrationHooks::class, 'initialHydrate'],

    ]);

    LifecycleManager::registerMountAccessMiddleware([

      [PerformAccessCheck::class, 'mountAccess'],

    ]);

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
