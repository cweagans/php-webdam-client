<?php

/**
 * @file
 * Contains the interface that all entity classes must implement.
 */

namespace cweagans\webdam\Entity;

interface EntityInterface {

  /**
   * Creates a new instance of the Entity given a JSON object from the Webdam API.
   *
   * @param string|object $json
   *   Either a JSON string or a json_decode()'d object.
   *
   * @return EntityInterface
   *   An instance of whatever class this method is being called on.
   */
  public static function fromJson($json);

}