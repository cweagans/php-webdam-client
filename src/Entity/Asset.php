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

  public $type_id;

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
   * @var string $description
   */
  public $description;

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

  public $date_created_unix;

  /**
   * @var string $datemodified
   */
  public $datemodified;

  public $date_modified_unix;

  /**
   * @var string $datecaptured
   */
  public $datecaptured;

  public $datecapturedUnix;

  public $mediaUrl;

  public $hiResMediaUrl;

  public $numExif;

  public $numXmp;

  public $pagecount;

  public $watched;

  public $numRelated;

  public $readonly;

  public $readonlynext;

  public $onhold;

  public $metadata;

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
   * @var array $additional_properties
   */
  public $xmp_metadata;

  /**
   * {@inheritdoc}
   */
  public static function fromJson($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $properties = [
      'id',
      'type_id',
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
      'description',
      'date_created_unix',
      'date_modified_unix',
      'datecapturedUnix',
      'mediaUrl',
      'hiResMediaUrl',
      'numExif',
      'numXmp',
      'pagecount',
      'watched',
      'numRelated',
      'readonly',
      'readonlynext',
      'onhold',
      'metadata',
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
      'type_id' => $this->type_id,
      'status' => $this->status,
      'filename' => $this->filename,
      'name' => $this->name,
      'filesize' => $this->filesize,
      'width' => $this->width,
      'height' => $this->height,
      'description' => $this->description,
      'filetype' => $this->filetype,
      'colorspace' => $this->colorspace,
      'version' => $this->version,
      'datecreated' => $this->datecreated,
      'date_created_unix' => $this->date_created_unix,
      'datemodified' => $this->datemodified,
      'date_modified_unix' => $this->date_modified_unix,
      'datecapturedUnix' => $this->datecapturedUnix,
      'datecaptured' => $this->datecaptured,
      'mediaUrl' => $this->mediaUrl,
      'hiResMediaUrl' => $this->hiResMediaUrl,
      'numExif' => $this->numExif,
      'numXmp' => $this->numXmp,
      'numComments' => $this->numComments,
      'pagecount' => $this->pagecount,
      'folder' => $this->folder,
      'user' => $this->user,
      'thumbnailurls' => $this->thumbnailurls,
      'watched' => $this->watched,
      'numRelated' => $this->numRelated,
      'readonly' => $this->readonly,
      'readonlynext' => $this->readonlynext,
      'onhold' => $this->onhold,
      'metadata' => $this->metadata,
    ];
  }

}