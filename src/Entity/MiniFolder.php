<?php

/**
 * @file
 * Describes Webdam's MiniFolder data type.
 */

namespace cweagans\webdam\Entity;

class MiniFolder implements EntityInterface, \JsonSerializable {

  /**
   * @var string $id
   */
  public $id;

  /**
   * @var string $name
   */
  public $name;

  /**
   * @var object $properties
   */
  public $properties;

  public static function fromJson($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $properties = [
      'id',
      'name',
      'properties',
    ];

    $minifolder = new static();
    foreach ($properties as $property) {
      if (isset($json->{$property})) {
        $minifolder->{$property} = $json->{$property};
      }
    }

    return $minifolder;
  }

  public function jsonSerialize() {
    $data = [
      'id' => $this->id,
      'type' => 'folder',
      'name' => $this->name,
    ];

    if (!is_null($this->properties)) {
      $data['properties'] = $this->properties;
    }

    return $data;
  }

}