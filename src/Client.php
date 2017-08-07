<?php

/**
 * @file
 * Provides API interface for Webdam.
 */

namespace cweagans\webdam;

use cweagans\webdam\Entity\Folder;
use cweagans\webdam\Entity\User;
use cweagans\webdam\Exception\InvalidCredentialsException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

class Client {

  /**
   * The version of this client. Used in User-Agent string for API requests.
   *
   * @var string
   */
  const CLIENTVERSION = "1.x-dev";

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
  protected $baseUrl = "https://apiv2.webdamdb.com";

  /**
   * The access token retreived from the Webdam authentication endpoint.
   */
  protected $accessToken;

  /**
   * Unix timestamp when $this->accessToken expires.
   */
  protected $accessTokenExpiry;

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

  /**
   * Authenticates with the Webdam service and retrieves an access token, or uses existing one.
   */
  public function checkAuth() {
    // If we have an unexpired access token, we're good to go.
    if (!is_null($this->accessToken) && time() < $this->accessTokenExpiry) {
      return;
    }

    // Otherwise, we need to authenticate and store the access token and expiry.
    $url = $this->baseUrl . '/oauth2/token';
    $data = [
      'grant_type' => 'password',
      'username' => $this->username,
      'password' => $this->password,
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
    ];

    /**
     * For error response body details:
     * @see \cweagans\webdam\tests\ClientTest::testInvalidClient()
     * @see \cweagans\webdam\tests\ClientTest::testInvalidGrant()
     *
     * For successful auth response body details:
     * @see \cweagans\webdam\tests\ClientTest::testSuccessfulAuthentication()
     */
    try {
      $response = $this->client->request("POST", $url, ['form_params' => $data]);

      // Body properties: access_token, expires_in, token_type, refresh_token
      $body = (string) $response->getBody();
      $body = json_decode($body);

      $this->accessToken = $body->access_token;
      $this->accessTokenExpiry = time() + $body->expires_in;
    }
    catch (ClientException $e) {
      // Looks like any form of bad auth with Webdam is a 400, but we're wrapping
      // it here just in case.
      if ($e->getResponse()->getStatusCode() == 400) {
        $body = (string) $e->getResponse()->getBody();
        $body = json_decode($body);

        throw new InvalidCredentialsException($body->error_description . ' (' . $body->error . ').');
      }
    }
  }

  /**
   * Return an array of headers to add to every authenticated request.
   *
   * Note that this should not be used for the initial authentication request, as
   * it will attempt to add an access token that we don't have yet.
   *
   * @return array
   */
  protected function getDefaultHeaders() {
    return [
      'User-Agent' => "cweagans/php-webdam-client " . self::CLIENTVERSION,
      'Accept' => 'application/json',
      'Authorization' => 'Bearer ' . $this->accessToken,
    ];
  }

  /**
   * Get internal auth state details.
   *
   * There shouldn't ever be a need to call this function in production, but it's
   * useful for debugging and testing.
   */
  public function getAuthState() {
    $state = [];

    if (!is_null($this->accessToken) && time() < $this->accessTokenExpiry) {
      return [
        'valid_token' => TRUE,
        'access_token' => $this->accessToken,
        'access_token_expiry' => $this->accessTokenExpiry,
      ];
    }

    return ['valid_token' => FALSE];
  }

  /**
   * Get subscription details for the account.
   *
   * @see https://www.damsuccess.com/hc/en-us/articles/202134055-REST-API#accountsubscription
   *
   * @todo Should this be an Entity?
   *
   * @return \stdClass
   *   Returns a stdClass with the following properties:
   *    - maxAdmins
   *    - numAdmins
   *    - maxContributors
   *    - numContributors
   *    - maxEndUsers
   *    - maxUsers
   *    - url
   *    - username
   *    - planDiskSpace
   *    - activeUsers
   *    - inactiveUsers
   */
  public function getAccountSubscriptionDetails() {
    $this->checkAuth();

    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/subscription',
      ['headers' => $this->getDefaultHeaders()]
    );

    $account = json_decode($response->getBody());

    return $account;
  }

  /**
   * Get a Folder given a Folder ID.
   *
   * @param int $folderID
   *   The webdam Folder ID.
   *
   * @return Folder
   */
  public function getFolder($folderID) {
    $this->checkAuth();

    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/folders/' . $folderID,
      ['headers' => $this->getDefaultHeaders()]
    );

    $folder = Folder::fromJson((string) $response->getBody());

    return $folder;
  }

  /**
   * Get top level folders.
   *
   * @return Folder[]
   */
  public function getTopLevelFolders() {
    $this->checkAuth();

    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/folders/0',
      ['headers' => $this->getDefaultHeaders()]
    );

    $folder_data = json_decode($response->getBody());

    $folders = [];
    foreach ($folder_data as $folder) {
      $folders[] = Folder::fromJson($folder);
    }

    return $folders;
  }

  /**
   * Get an Asset given an Asset ID.
   *
   * @param int $assetId
   *   The webdam Asset ID.
   *
   * @return Asset
   */
  public function getAsset($assetId) {
    $this->checkAuth();

    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/assets/' . $assetId,
      ['headers' => $this->getDefaultHeaders()]
    );

    return (string) $response->getBody();
  }

  /**
   * Gets presigned url from AWS S3.
   *
   * @param array $file_data
   *   The file data required by Webdam
   *   (filesize, folder_id, filename, contenttype).
   *
   * @return mixed
   *   Presigned url needed for next step + PID.
   */
  public function getPresignUrl(array $file_data) {
    $this->checkAuth();

    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/ws/awss3/generateupload',
      [
        'headers' => $this->getDefaultHeaders(),
        'query' => $file_data,
      ]
    );
    return json_decode($response->getBody());
  }

  /**
   * Uploads file to Webdam AWS S3.
   *
   * @param mixed $presignedUrl
   *   The presigned URL we got in previous step from AWS.
   * @param string $file_uri
   *   The file URI.
   * @param string $file_type
   *   The File Content Type.
   * @return array
   *   Response Status 100 / 200
   */
  public function uploadPresigned($presignedUrl, $file_uri, $file_type) {
    $this->checkAuth();

    $response = $this->client->request(
      "PUT",
      $presignedUrl, [
        'headers' => ['Content-Type' => $file_type],
        'body' => $file_uri,
      ]);
    return [
      'status' => json_decode($response->getStatusCode(), TRUE),
    ];

  }

  /**
   * Confirms the upload to Webdam.
   *
   * @param string $pid
   *   The Process ID we got in first step.
   *
   * @return string
   *   The uploaded/edited asset ID.
   */
  public function uploadConfirmed($pid) {
    $this->checkAuth();

    $response = $this->client->request(
      "PUT",
      $this->baseUrl . '/ws/awss3/finishupload/' . $pid,
      ['headers' => $this->getDefaultHeaders()]
    );

    return (string) json_decode($response->getBody(), TRUE)['id'];

  }

  /**
   * Uploads Assets to Webdam using the previously defined methods.
   *
   * @param array $file_data
   *   The file data required by Webdam.
   * @param int $folderID
   *   The Webdam folder ID.
   *
   * @return array
   *   Webdam response.
   */
  public function uploadAsset(array $file_data, $folderID = NULL) {
    $this->checkAuth();

    if ($folderID != NULL) {
      $file_data['folderid'] = $folderID;
    }
    $file_uri = $file_data['file_uri'];
    $file_type = $file_data['contenttype'];
    $response = [];
    // Getting Pre-sign URL.
    $presign = $this->getPresignUrl($file_data);
    if (property_exists($presign, 'presignedUrl')) {
      // Post-sign upload.
      $postsign = $this->uploadPresigned($presign->presignedUrl, $file_uri, $file_type);
      $response['processId'] = $presign->processId;
      $response['presignUrl'] = $presign->presignedUrl;
      $response['post_status'] = $postsign['status'];

      if ($postsign['status'] == '200' || $postsign['status'] == '100') {
        // Getting Asset ID.
        $uploadConfirm = $this->uploadConfirmed($presign->processId);
        $response['id'] = $uploadConfirm;
      }
      else {
        $response['error'] = 'Failed to upload file after presigning.';
      }
    }
    else {
      $response['error'] = 'Failed to obtain presigned URL from Webdam.';
    }

    return $response;
  }

}
