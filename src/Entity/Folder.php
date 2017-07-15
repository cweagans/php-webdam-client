<?php

/**
 * @file
 * Describes Webdam's Folder data type.
 */

namespace cweagans\webdam\Entity;

class Folder implements EntityInterface, \JsonSerializable {

  /**
   * @var string $id
   */
  public $id;

  /**
   * @var string $metadatatemplateid
   */
  public $metadatatemplateid;

  /**
   * This is a folder ID, not a Folder object.
   *
   * @var string $parent
   */
  public $parent;

  /**
   * @var string $name
   */
  public $name;

  /**
   * @var string $status
   */
  public $status;

  /**
   * @var MiniUser $user
   */
  public $user;

  /**
   * @var string $datecreated
   */
  public $datecreated;

  /**
   * @var string $date_created_unix
   */
  public $date_created_unix;

  /**
   * @var string $eventdate
   */
  public $eventdate;

  /**
   * @var string $date_event_unix
   */
  public $date_event_unix;

  /**
   * @var bool $passwordprotected
   */
  public $passwordprotected;

  /**
   * @var string $numassets
   */
  public $numassets;

  /**
   * @var string $numchildren
   */
  public $numchildren;

  /**
   * Guaranteed to be unique.
   *
   * @var string $clientfolderid
   */
  public $clientfolderid;

  /**
   * @var object $permissions
   */
  public $permissions;

  /**
   * @var object $properties
   */
  public $properties;

  /**
   * @var array $thumbnailurls
   */
  public $thumbnailurls;

  /**
   * @var Folder[] $folders
   */
  public $folders;

  public static function fromJson($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $properties = [
      'id',
      'metadatatemplateid',
      'parent',
      'name',
      'status',
      'datecreated',
      'passwordprotected',
      'numassets',
      'numchildren',
      'clientfolderid',
      'permissions',
      'properties',
      'thumbnailurls',
      'date_created_unix',
      'eventdate',
    ];

    $folder = new static();
    foreach ($properties as $property) {
      if (isset($json->{$property})) {
        $folder->{$property} = $json->{$property};
      }
    }

    // Add a MiniUser if necessary.
    if (isset($json->user)) {
      $folder->user = MiniUser::fromJson($json->user);
    }

    // Add Folder objects.
    $folders = [];
    if (!empty($json->folders)) {
      foreach ($json->folders as $folderdata) {
        $folders[] = Folder::fromJson($folderdata);
      }
    }
    $folder->folders = $folders;

    return $folder;
  }

  public function jsonSerialize() {
    $properties = [
      'id' => $this->id,
      'type' => 'folder',
      'metadatatemplateid' => $this->metadatatemplateid,
      'name' => $this->name,
      'parent' => $this->parent,
      'status' => $this->status,
      'passwordprotected' => $this->passwordprotected,
      'datecreated' => $this->datecreated,
      'date_created_unix' => $this->date_created_unix,
      'eventdate' => $this->eventdate,
      'date_event_unix' => $this->date_event_unix,
      'thumbnailurls' => $this->thumbnailurls,
      'clientfolderid' => $this->clientfolderid,
      'numassets' => $this->numassets,
      'numchildren' => $this->numchildren,
      'properties' => $this->properties,
      'permissions' => $this->permissions,
      'user' => $this->user,
    ];

    if (!empty($this->folders)) {
      $properties['folders'] = $this->folders;
    }

    return $properties;
  }

}