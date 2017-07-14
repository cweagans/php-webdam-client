<?php

/**
 * @file
 * Provides API interface for Webdam.
 */

namespace cweagans\webdam;

use GuzzleHttp\ClientInterface;

class Client {

  /**
   * The Guzzle client to use for communication with the Webdam API.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The username for the Webdam API account.
   *
   * @var string
   */
  protected $username;

  /**
   * The password for the Webdam API account.
   *
   * @var string
   */
  protected $password;

  /**
   * The client ID provided by Webdam for API communication.
   *
   * @var string
   */
  protected $clientId;

  /**
   * The client secret provided by Webdam for API communication.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * The base URL of the webdam API.
   */
  protected $baseUrl = "https://apiv2.webdamdb.com/";

  /**
   * Client constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @param $username
   * @param $password
   * @param $client_id
   * @param $client_secret
   */
  public function __construct(ClientInterface $client, $username, $password, $client_id, $client_secret) {
    $this->client = $client;
    $this->username = $username;
    $this->password = $password;
    $this->clientId = $client_id;
    $this->clientSecret = $client_secret;
  }

}