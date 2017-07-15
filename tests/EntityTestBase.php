<?php

namespace cweagans\webdam\tests;

use PHPUnit\Framework\TestCase;

abstract class EntityTestBase extends TestCase {

  protected function loadJson($type) {
    $json = file_get_contents(__DIR__ . '/json/' . $type . '.json');
    $json = trim($json);
    return $json;
  }

}