<?php

namespace Drupal\wire;

class AddAttributesToRootTagOfHtml {

  public function __invoke($dom, $data): string {
    $attributesFormattedForHtmlElement = collect($data)
      ->mapWithKeys(function ($value, $key) {
        return ["wire:{$key}" => $this->escapeStringForHtml($value)];
      })->map(function ($value, $key) {
        return sprintf('%s="%s"', $key, $value);
      })->implode(' ');

    preg_match('/(?:\n\s*|^\s*)<([a-zA-Z0-9\-]+)/', $dom, $matches, PREG_OFFSET_CAPTURE);

    throw_unless(
      count($matches),
      new \Exception('Wire encountered a missing root tag when trying to render a component. \n When rendering a Twig, make sure it contains a root HTML tag.')
    );

    $tagName = $matches[1][0];
    $lengthOfTagName = strlen($tagName);
    $positionOfFirstCharacterInTagName = $matches[1][1];

    return substr_replace(
      $dom,
      ' ' . $attributesFormattedForHtmlElement,
      $positionOfFirstCharacterInTagName + $lengthOfTagName,
      0
    );
  }

  protected function escapeStringForHtml($subject): string {
    if (is_string($subject) || is_numeric($subject)) {
      return htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE);
    }

    return htmlspecialchars(json_encode($subject), ENT_QUOTES | ENT_SUBSTITUTE);
  }

}
