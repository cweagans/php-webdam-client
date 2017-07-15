<?php

/**
 * @file
 * Tests cweagans\webdam\Entity\Folder.
 */

namespace cweagans\webdam\tests;

use cweagans\webdam\Entity\Folder;

class FolderEntityTest extends EntityTestBase {

  public function testEntity() {
    $json = $this->loadJson('folder');
    $folder = Folder::fromJson($json);
    $this->assertEquals("2177680", $folder->id);
    $this->assertEquals("23456", $folder->metadatatemplateid);
    $this->assertEquals("0", $folder->parent);
    $this->assertEquals("GIS Data", $folder->name);
    $this->assertEquals("active", $folder->status);
    $this->assertEquals("2017-03-28 09:58:01", $folder->datecreated);
    $this->assertEquals("1490720281", $folder->date_created_unix);
    $this->assertEquals("false", $folder->passwordprotected);
    $this->assertEquals("4", $folder->numassets);
    $this->assertEquals("0", $folder->numchildren);
    $this->assertEquals(NULL, $folder->clientfolderid);
    $this->assertObjectHasAttribute("folders", $folder->permissions);
    $this->assertObjectHasAttribute("assets", $folder->permissions);
    $this->assertTrue(is_array($folder->permissions->folders));
    $this->assertTrue(is_array($folder->permissions->assets));
    $this->assertNotEmpty($folder->permissions->folders);
    $this->assertNotEmpty($folder->permissions->assets);
    $this->assertTrue(is_array($folder->thumbnailurls));
    $this->assertNotEmpty($folder->thumbnailurls);
    $this->assertInstanceOf("cweagans\webdam\Entity\Miniuser", $folder->user);
    $this->assertEquals("275508", $folder->user->id);
    $this->assertEquals("jdoe@example.com", $folder->user->email);
    $this->assertEquals("John Doe", $folder->user->name);
    $this->assertEquals("jdoe", $folder->user->username);
    $this->assertEquals($json, json_encode($folder, JSON_PRETTY_PRINT));
  }

  public function testEntityWithChildren() {
    $json = $this->loadJson('folder_with_children');
    $folder = Folder::fromJson($json);

    $this->assertNotEmpty($folder->folders);
    $this->assertCount(2, $folder->folders);
    $this->assertEmpty($folder->folders[0]->folders);
    $this->assertEmpty($folder->folders[1]->folders);

    $this->assertEquals($json, json_encode($folder, JSON_PRETTY_PRINT));
  }

}