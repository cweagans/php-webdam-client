<?php

/**
 * @file
 * Tests cweagans\webdam\Entity\Asset.
 */

namespace cweagans\webdam\tests;

use cweagans\webdam\Entity\Asset;

class AssetEntityTest extends EntityTestBase {

  public function testEntity() {
    $json = $this->loadJson('asset');
    $asset = Asset::fromJson($json);
    $this->assertEquals($json, json_encode($asset, JSON_PRETTY_PRINT));
  }

}