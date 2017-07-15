<?php

/**
 * @file
 * Tests cweagans\webdam\Entity\MiniFolder.
 */

namespace cweagans\webdam\tests;

use cweagans\webdam\Entity\MiniFolder;

class MiniFolderEntityTest extends EntityTestBase {

  public function testEntity() {
    $json = $this->loadJson('minifolder');
    $minifolder = MiniFolder::fromJson($json);
    $this->assertEquals("12345", $minifolder->id);
    $this->assertEquals("testFolder", $minifolder->name);
    $this->assertEquals($json, json_encode($minifolder, JSON_PRETTY_PRINT));
  }

}