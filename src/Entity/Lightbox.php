<?php

/**
 * @file
 * Describes Webdam's Lightbox data type.
 */

namespace cweagans\webdam\Entity;

class Lightbox implements EntityInterface, \JsonSerializable {

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
   * @var string $project
   */
  public $project;

  /**
   * @var string $datecreated
   */
  public $datecreated;

  /**
   * @var MiniUser $user
   */
  public $user;

  /**
   * @var bool $share
   */
  public $share;

  /**
   * @var bool $canedit
   */
  public $canedit;

  /**
   * @var string $numCollaborators
   */
  public $numCollaborators;

  /**
   * @var string $numComments
   */
  public $numComments;

  /**
   * @var string $numberitems
   */
  public $numberitems;

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
      'project',
      'datecreated',
      'share',
      'canedit',
      'numCollaborators',
      'numComments',
      'numberitems',
    ];

    $lightbox = new static();
    foreach ($properties as $property) {
      if (isset($json->{$property})) {
        $lightbox->{$property} = $json->{$property};
      }
    }

    // Add a MiniUser if necessary.
    if (isset($json->user)) {
      $lightbox->user = MiniUser::fromJson($json->user);
    }

    return $lightbox;
  }

  public function jsonSerialize() {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
      'project' => $this->project,
      'datecreated' => $this->datecreated,
      'share' => $this->share,
      'canedit' => $this->canedit,
      'numCollaborators' => $this->numCollaborators,
      'numComments' => $this->numComments,
      'numberitems' => $this->numberitems,
      'user' => $this->user,
    ];
  }

}