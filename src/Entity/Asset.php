<?php

/**
 * @file
 * Describes Webdam's Asset data type.
 */

namespace cweagans\webdam\Entity;

class Asset implements EntityInterface, \JsonSerializable {

  /**
   * @var string $id
   */
  public $id;

  /**
   * "active" or "inactive"
   *
   * @var string $status
   */
  public $status;

  /**
   * @var string $filename
   */
  public $filename;

  /**
   * @var string $version
   */
  public $version;

  /**
   * @var string $name
   */
  public $name;

  /**
   * @var string $filesize
   */
  public $filesize;

  /**
   * @var string $width
   */
  public $width;

  /**
   * @var string $height
   */
  public $height;

  /**
   * @var string $filetype
   */
  public $filetype;

  /**
   * @var string $colorspace
   */
  public $colorspace;

  /**
   * @var stdClass $thumbnailurls
   */
  public $thumbnailurls;

  /**
   * @var string $datecreated
   */
  public $datecreated;

  /**
   * @var string $datemodified
   */
  public $datemodified;

  /**
   * @var string $datecaptured
   */
  public $datecaptured;

  /**
   * @var string $numComments
   */
  public $numComments;

  /**
   * @var MiniUser $user
   */
  public $user;

  /**
   * @var MiniFolder $folder
   */
  public $folder;

  /**
   * {@inheritdoc}
   */
  public static function fromJson($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $properties = [
      'id',
      'status',
      'filename',
      'version',
      'name',
      'filesize',
      'width',
      'height',
      'filetype',
      'colorspace',
      'thumbnailurls',
      'datecreated',
      'datemodified',
      'datecaptured',
      'numComments',
    ];

    // Copy all of the simple properties.
    $asset = new static();
    foreach ($properties as $property) {
      if (isset($json->{$property})) {
        $asset->{$property} = $json->{$property};
      }
    }

    if (isset($json->user)) {
      $asset->user = MiniUser::fromJson($json->user);
    }

    if (isset($json->folder)) {
      $asset->folder = MiniFolder::fromJson($json->folder);
    }

    return $asset;
  }

  public function jsonSerialize() {
    return [
      'id' => $this->id,
      'type' => 'asset',
      'status' => $this->status,
      'filename' => $this->filename,
      'version' => $this->version,
      'name' => $this->name,
      'filesize' => $this->filesize,
      'width' => $this->width,
      'height' => $this->height,
      'filetype' => $this->filetype,
      'colorspace' => $this->colorspace,
      'thumbnailurls' => $this->thumbnailurls,
      'datecreated' => $this->datecreated,
      'datemodified' => $this->datemodified,
      'datecaptured' => $this->datecaptured,
      'numComments' => $this->numComments,
      'user' => $this->user,
      'folder' => $this->folder,
    ];
  }

}