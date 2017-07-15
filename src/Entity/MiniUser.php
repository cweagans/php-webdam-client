<?php

/**
 * @file
 * Describes Webdam's MiniUser data type.
 */

namespace cweagans\webdam\Entity;

class MiniUser implements EntityInterface, \JsonSerializable {

  /**
   * @var string $id
   */
  public $id;

  /**
   * @var string $email
   */
  public $email;

  /**
   * @var string $name
   */
  public $name;

  /**
   * @var string $username
   */
  public $username;

  /**
   * @var string $status
   */
  public $status;

  /**
   * {@inheritdoc}
   */
  public static function fromJson($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $properties = [
      'id',
      'email',
      'name',
      'username',
      'status',
    ];

    $miniuser = new static();
    foreach ($properties as $property) {
      if (isset($json->{$property})) {
        $miniuser->{$property} = $json->{$property};
      }
    }

    return $miniuser;
  }

  public function jsonSerialize() {
    return [
      'id' => $this->id,
      'type' => 'user',
      'email' => $this->email,
      'name' => $this->name,
      'username' => $this->username,
      'status' => $this->status,
    ];
  }

}