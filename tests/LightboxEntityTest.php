<?php

/**
 * @file
 * Tests cweagans\webdam\Entity\MiniUser.
 */

namespace cweagans\webdam\tests;

use cweagans\webdam\Entity\Lightbox;

class LightboxEntityTest extends EntityTestBase {

  public function testEntity() {
    $json = $this->loadJson('lightbox');
    $lightbox = Lightbox::fromJson($json);

    $this->assertEquals("12345", $lightbox->id);
    $this->assertEquals("Test Lightbox", $lightbox->name);
    $this->assertEquals("Lightbox for testing", $lightbox->description);
    $this->assertEquals("My project", $lightbox->project);
    $this->assertEquals("2009-10-09 20:34:36", $lightbox->datecreated);
    $this->assertEquals("false", $lightbox->share);
    $this->assertEquals("true", $lightbox->canedit);
    $this->assertEquals("123", $lightbox->numCollaborators);
    $this->assertEquals("234", $lightbox->numComments);
    $this->assertEquals("456", $lightbox->numberitems);
    $this->assertInstanceOf("cweagans\webdam\Entity\MiniUser", $lightbox->user);
    $this->assertEquals("12345", $lightbox->user->id);
    $this->assertEquals("jdoe@example.com", $lightbox->user->email);
    $this->assertEquals("John Doe", $lightbox->user->name);
    $this->assertEquals("jdoe", $lightbox->user->username);

    $this->assertEquals($json, json_encode($lightbox, JSON_PRETTY_PRINT));
  }

}
