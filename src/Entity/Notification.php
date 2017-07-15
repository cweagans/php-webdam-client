<?php

/**
 * @file
 * Describes Webdam's Notification data type.
 */

namespace cweagans\webdam\Entity;

class Notification implements EntityInterface, \JsonSerializable {

  /**
   * @var string $id
   */
  public $id;

  /**
   * See https://www.damsuccess.com/hc/en-us/articles/202134055-REST-API#availablenotificationtypes
   * for possible values.
   *
   * @var string $action
   */
  public $action;

  /**
   * @var MiniUser $user
   */
  public $user;

  /**
   * @var \stdClass $source
   */
  public $source;

  /**
   * @var \stdClass $subitems
   */
  public $subitems;

  /**
   * @var string $displaystring
   */
  public $displaystring;

  /**
   * {@inheritdoc}
   */
  public static function fromJson($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $properties = [
      'id',
      'action',
      'source',
      'subitems',
      'displaystring',
    ];

    $notification = new static();
    foreach ($properties as $property) {
      if (isset($json->{$property})) {
        $notification->{$property} = $json->{$property};
      }
    }

    // Add a MiniUser if necessary.
    if (isset($json->user)) {
      $notification->user = MiniUser::fromJson($json->user);
    }

    return $notification;
  }

  public function jsonSerialize() {
    return [
      'id' => $this->id,
      'type' => 'notification',
      'action' => $this->action,
      'user' => $this->user,
      'source' => $this->source,
      'subitems' => $this->subitems,
      'displaystring' => $this->displaystring,
    ];
  }

}