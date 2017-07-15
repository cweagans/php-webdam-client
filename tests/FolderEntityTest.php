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
    $this->assertEquals("12345", $folder->id);
    $this->assertEquals("23456", $folder->metadatatemplateid);
    $this->assertEquals("45678", $folder->parent);
    $this->assertEquals("Folder 1", $folder->name);
    $this->assertEquals("active", $folder->status);
    $this->assertEquals("2011-09-23 13:17:48", $folder->datecreated);
    $this->assertEquals("false", $folder->passwordprotected);
    $this->assertEquals("123", $folder->numassets);
    $this->assertEquals("0", $folder->numchildren);
    $this->assertEquals("680da8c4-bb91-47be-a8a2-e6140ad21aaf", $folder->clientfolderid);
    $this->assertObjectHasAttribute("folders", $folder->permissions);
    $this->assertObjectHasAttribute("assets", $folder->permissions);
    $this->assertTrue(is_array($folder->permissions->folders));
    $this->assertTrue(is_array($folder->permissions->assets));
    $this->assertNotEmpty($folder->permissions->folders);
    $this->assertNotEmpty($folder->permissions->assets);
    $this->assertTrue(is_array($folder->thumbnailurls));
    $this->assertNotEmpty($folder->thumbnailurls);
    $this->assertInstanceOf("cweagans\webdam\Entity\Miniuser", $folder->user);
    $this->assertEquals("12345", $folder->user->id);
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