<?php

namespace Drupal\wire\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

trait CanPretendToBeAFile {

  public function pretendResponseIsFile($file, $mimeType = 'application/javascript'): BinaryFileResponse|Response {

    $expires = strtotime('+1 year');
    $lastModified = filemtime($file);
    $cacheControl = 'public, max-age=31536000';

    $matchesCache = function ($lastModified) {
      $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
      return @strtotime($ifModifiedSince) === $lastModified;
    };

    $httpDate = function ($lastModified) {
      return sprintf('%s GMT', gmdate('D, d M Y H:i:s', $lastModified));
    };

    if ($matchesCache($lastModified)) {
      $response = new Response();
      $response->setNotModified();
      return $response;
    }

    return new BinaryFileResponse($file, 200, [
      'Content-Type' => "$mimeType; charset=utf-8",
      'Cache-Control' => $cacheControl,
      'Expires' => $httpDate($expires),
      'Last-Modified' => $httpDate($lastModified),
    ]);
  }

}
