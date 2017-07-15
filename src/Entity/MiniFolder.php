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

  public static function fromJson($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $properties = [
      'id',
      'name',
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
    return [
      'id' => $this->id,
      'type' => 'folder',
      'name' => $this->name,
    ];
  }

}