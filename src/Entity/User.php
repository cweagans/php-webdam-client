<?php

/**
 * @file
 * Describes Webdam's User data type.
 */

namespace cweagans\webdam\Entity;

class User implements EntityInterface, \JsonSerializable {

  /**
   * @var string $id
   */
  public $id;

  /**
   * @var string $username
   */
  public $username;

  /**
   * @var string $first
   */
  public $first;

  /**
   * @var string $last
   */
  public $last;

  /**
   * @var string $name
   */
  public $name;

  /**
   * @var string $email
   */
  public $email;

  /**
   * @var string $company
   */
  public $company;

  /**
   * @var string $companyurl
   */
  public $companyurl;

  /**
   * @var string $phone
   */
  public $phone;

  /**
   * @var string $fax
   */
  public $fax;

  /**
   * @var string $country
   */
  public $country;

  /**
   * @var string $city
   */
  public $city;

  /**
   * @var string $address1
   */
  public $address1;

  /**
   * @var string $address2
   */
  public $address2;

  /**
   * @var bool $status
   */
  public $status;

  /**
   * @var string $datecreated
   */
  public $datecreated;

  /**
   * @var string $lastlogin
   */
  public $lastlogin;

  /**
   * @var bool $sendemail
   */
  public $sendemail;

  /**
   * @var Group[] $groups
   */
  public $groups;

  /**
   * {@inheritdoc}
   */
  public static function fromJson($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $properties = [
      'id',
      'username',
      'first',
      'last',
      'name',
      'email',
      'company',
      'companyurl',
      'phone',
      'fax',
      'country',
      'city',
      'address1',
      'address2',
      'status',
      'datecreated',
      'lastlogin'
    ];

    // Copy all of the simple properties.
    $user = new static();
    foreach ($properties as $property) {
      if (isset($json->{$property})) {
        $user->{$property} = $json->{$property};
      }
    }

    // If there are groups, create Group objects and add them to the User.
    $groups = [];
    if (!empty($json->groups)) {
      foreach ($json->groups as $group) {
        $groups[] = Group::fromJson($group);
      }
    }
    $user->groups = $groups;

    return $user;
  }

  public function jsonSerialize() {
    return [
      'id' => $this->id,
      'type' => 'user',
      'username' => $this->username,
      'first' => $this->first,
      'last' => $this->last,
      'name' => $this->name,
      'email' => $this->email,
      'company' => $this->company,
      'companyurl' => $this->companyurl,
      'phone' => $this->phone,
      'fax' => $this->fax,
      'country' => $this->country,
      'city' => $this->city,
      'address1' => $this->address1,
      'address2' => $this->address2,
      'status' => $this->status,
      'datecreated' => $this->datecreated,
      'lastlogin' => $this->lastlogin,
      'groups' => $this->groups,
    ];
  }

}