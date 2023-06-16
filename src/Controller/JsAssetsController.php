<?php

namespace Drupal\wire\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller to output XML sitemap.
 */
class JsAssetsController extends ControllerBase {

  use CanPretendToBeAFile;

  protected static bool $debug;

  public function __construct(protected RequestStack $requestStack) {
    self::$debug = static::getDebugParameter();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  public function init(): Response {

    $response = new Response();
    $response->headers->set('Content-Type', 'application/javascript; charset=utf-8');
    $response->setContent($this->scripts());

    // The content of this file should not change except for when dev mode is turned on and on deploys
    // (query string will change) so, should be safe to add generic 1 month cache.
    $response->headers->set('Cache-Control', 'public, max-age=2630000');

    return $response;
  }

  public function source(): Response {
    return $this->pretendResponseIsFile(__DIR__ . '/../../dist/wire.js');
  }

  public function sourceMap(): Response {
    return $this->pretendResponseIsFile(__DIR__ . '/../../dist/wire.js.map');
  }

  public function alpinejs(): Response {
    return $this->pretendResponseIsFile(__DIR__ . '/../../dist/alpinejs@3.12.1.min.js');
  }

  public function turbolinks(): Response {
    return $this->pretendResponseIsFile(__DIR__ . '/../../dist/turbolinks.js');
  }

  public function styles(): Response {
    return $this->pretendResponseIsFile(__DIR__ . '/../../css/styles.css', 'text/css');
  }

  public function scripts(): string {
    $scripts = $this->javaScriptAssets();
    $html = self::$debug ? ['<!-- Wire Scripts -->'] : [];
    $html[] = self::$debug ? $scripts : $this->minify($scripts);
    return implode(PHP_EOL, $html);
  }

  protected function javaScriptAssets(): string {

    $appUrl = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();

    $devTools = NULL;
    $windowAlpineCheck = NULL;
    if (self::$debug) {
      $devTools = 'window.wire.devTools(true);';

      $windowAlpineCheck = <<<'HTML'
  /* Make sure Wire loads first. */
	if (window.Alpine) {
	    /* Defer showing the warning so it doesn't get buried under downstream errors. */
	    document.addEventListener("DOMContentLoaded", function () {
	        setTimeout(function() {
	            console.warn("Wire: It looks like AlpineJS has already been loaded. Make sure Wire\'s scripts are loaded before Alpine.\\n\\n Reference docs for more info: https://wire-drupal.com/docs/alpine-js")
	        })
	    });
	}

	/* Make Alpine wait until Wire is finished rendering to do its thing. */
HTML;

    }

    return <<<HTML

    if (window.wire === undefined) {
      window.wire = new Wire();
      {$devTools}
      window.Wire = window.wire;
      window.wire_app_url = '{$appUrl}';
    }

	{$windowAlpineCheck}
    window.deferLoadingAlpine = function (callback) {
        window.addEventListener('wire:load', function () {
            callback();
        });
    };

    var started = false;

    window.addEventListener('alpine:initializing', function () {
        if (! started) {
            window.wire.start();

            started = true;
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        if (! started) {
            window.wire.start();

            started = true;
        }
    });
HTML;
  }

  protected function minify($content): string {
    return preg_replace('~(\v|\t|\s{2,})~m', '', $content);
  }

  private static function getDebugParameter(): bool {
    $debug = FALSE;
    $container = \Drupal::getContainer();
    if ($container->hasParameter('twig.config') && is_array($container->getParameter('twig.config'))) {
      $debug = $container->getParameter('twig.config')['debug'];
    }
    return $debug;
  }


}
