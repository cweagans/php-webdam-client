<?php

/**
 * @file
 * Tests cweagans\webdam\Entity\Notification.
 */

namespace cweagans\webdam\tests;

use cweagans\webdam\Entity\Notification;

class NotificationEntityTest extends EntityTestBase {

  public function testEntity() {
    $json = $this->loadJson('notification');
    $notification = Notification::fromJson($json);
    $this->assertEquals("2952", $notification->id);
    $this->assertEquals("comment", $notification->action);
    $this->assertInstanceOf('cweagans\webdam\Entity\MiniUser', $notification->user);
    $this->assertTrue(is_object($notification->source));
    $this->assertEquals("lightbox", $notification->source->type);
    $this->assertEquals("39438", $notification->source->id);
    $this->assertEquals("lightboxname", $notification->source->name);
    $this->assertTrue(is_object($notification->subitems));
    $this->assertEquals("comment", $notification->subitems->type);
    $this->assertEquals("commentstring", $notification->subitems->comment);
    $this->assertEquals("DisplayString", $notification->displaystring);
    $this->assertEquals($json, json_encode($notification, JSON_PRETTY_PRINT));
  }

}