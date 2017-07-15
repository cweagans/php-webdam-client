<?php

/**
 * @file
 * Describes Webdam's Group data type.
 */

namespace cweagans\webdam\Entity;

class Group implements EntityInterface, \JsonSerializable {

  /**
   * @var string $id
   */
  public $id;

  /**
   * @var string $name
   */
  public $name;

  /**
   * @var string $description
   */
  public $description;

  /**
   * @var string $role
   */
  public $role;

  /**
   * @var int $numusers
   */
  public $numusers;

  /**
   * {@inheritdoc}
   */
  public static function fromJson($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $properties = [
      'id',
      'name',
      'description',
      'role',
      'numusers',
    ];

    $group = new static();
    foreach ($properties as $property) {
      if (isset($json->{$property})) {
        $group->{$property} = $json->{$property};
      }
    }

    return $group;
  }

  public function jsonSerialize() {
    return [
      'id' => $this->id,
      'type' => 'group',
      'name' => $this->name,
      'description' => $this->description,
      'role' => $this->role,
      'numusers' => $this->numusers,
    ];
  }

}