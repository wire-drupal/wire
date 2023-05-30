<?php

namespace Drupal\wire;

interface Wireable {

  public function toWire();

  public static function fromWire($value);

}
