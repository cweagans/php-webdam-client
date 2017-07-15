<?php

/**
 * @file
 * Tests cweagans\webdam\Entity\User.
 */

namespace cweagans\webdam\tests;

use cweagans\webdam\Entity\User;

class UserEntityTest extends EntityTestBase {

  public function testEntity() {
    $json = $this->loadJson('user');
    $user = User::fromJson($json);
    $this->assertEquals("12345", $user->id);
    $this->assertEquals("jdoe", $user->username);
    $this->assertEquals("John", $user->first);
    $this->assertEquals("Smith", $user->last);
    $this->assertEquals("John Doe", $user->name);
    $this->assertEquals("jdoe@example.com", $user->email);
    $this->assertEquals("Test Company", $user->company);
    $this->assertEquals("example.com", $user->companyurl);
    $this->assertEquals("208-123-4567", $user->phone);
    $this->assertEquals("208-543-9876", $user->fax);
    $this->assertEquals("United States", $user->country);
    $this->assertEquals("Boise", $user->city);
    $this->assertEquals("123 Main Street", $user->address1);
    $this->assertEquals("Unit B", $user->address2);
    $this->assertEquals("active", $user->status);
    $this->assertEquals("2009-10-09 20:34:36", $user->datecreated);
    $this->assertEquals("2013-07-10 12:04:24", $user->lastlogin);
    $this->assertTrue(is_array($user->groups));
    $this->assertEquals(1, count($user->groups));
    $this->assertInstanceOf("cweagans\webdam\Entity\Group", $user->groups[0]);
    $this->assertEquals("21", $user->groups[0]->id);
    $this->assertEquals("Admin", $user->groups[0]->name);
    $this->assertEquals("Group Description", $user->groups[0]->description);
    $this->assertEquals("Admin", $user->groups[0]->role);
    $this->assertEquals(123, $user->groups[0]->numusers);

    $this->assertEquals($json, json_encode($user, JSON_PRETTY_PRINT));
  }

}