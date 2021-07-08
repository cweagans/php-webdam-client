<?php

/**
 * @file
 * Provides API interface for Webdam.
 */

namespace cweagans\webdam;

use cweagans\webdam\Entity\Asset;
use cweagans\webdam\Entity\Folder;
use cweagans\webdam\Entity\MiniFolder;
use cweagans\webdam\Entity\User;
use cweagans\webdam\Exception\InvalidCredentialsException;
use cweagans\webdam\Exception\UploadAssetException;
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
   * A flag for determining if a token has been manually set.
   *
   * @var bool
   */
  protected $manualToken = FALSE;

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

    if ($this->manualToken) {
      throw new InvalidCredentialsException('Cannot reauthenticate a manually set token.');
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
   * Set the internal auth token.
   *
   * @param string $token
   * @param int $token_expiry
   */
  public function setToken($token, $token_expiry) {
    $this->manualToken = TRUE;
    $this->accessToken = $token;
    $this->accessTokenExpiry = $token_expiry;
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
      [
        'headers' => $this->getDefaultHeaders(),
        'query' => [
          'getchildren' => 'false',
        ],
      ]
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
   * @param bool $include_xmp
   *   If TRUE, $this->getAssetMetadata() will be called and the result will
   *   be added to the returned asset object.
   *
   * @return Asset
   */
  public function getAsset($assetId, $include_xmp = FALSE) {
    $this->checkAuth();

    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/assets/' . $assetId,
      ['headers' => $this->getDefaultHeaders()]
    );

    $asset = Asset::fromJson((string) $response->getBody());

    if ($include_xmp) {
      $asset->xmp_metadata = $this->getAssetMetadata($assetId);
    }

    return $asset;
  }

  /**
   * Gets presigned url from AWS S3.
   *
   * @param string $file_type
   *   The File Content Type.
   * @param string $file_name
   *   The File filename.
   * @param string $file_size
   *   The File size.
   * @param string $folderID
   *   The folder ID to upload the file to.
   *
   * @return mixed
   *   Presigned url needed for next step + PID.
   */
  protected function getPresignUrl($file_type, $file_name, $file_size, $folderID) {
    $this->checkAuth();

    $file_data = [
      'filesize' => $file_size,
      'filename' => $file_name,
      'contenttype' => $file_type,
      'folderid' => $folderID,
    ];
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
   *
   * @return array
   *   Response Status 100 / 200
   */
  protected function uploadPresigned($presignedUrl, $file_uri, $file_type) {
    $this->checkAuth();

    $file = fopen($file_uri, 'r');
    $response = $this->client->request(
      "PUT",
      $presignedUrl, [
        'headers' => ['Content-Type' => $file_type],
        'body' => stream_get_contents($file),
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
  protected function uploadConfirmed($pid) {
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
   * @param string $file_uri
   *   The file URI.
   * @param string $file_name
   *   The File filename.
   * @param int $folderID
   *   The Webdam folder ID.
   *
   * @throws UploadAssetException
   *   If uploadAsset fails we throw an instance of UploadAssetException
   *   that contains a message for the caller.
   *
   * @return string
   *   Webdam response (asset id).
   */
  public function uploadAsset($file_uri, $file_name, $folderID) {
    $this->checkAuth();

    //Getting file data from file_uri
    $file_type = mime_content_type($file_uri);
    $file_size = filesize($file_uri);

    $response = [];
    // Getting Pre-sign URL.
    $presign = $this->getPresignUrl($file_type, $file_name, $file_size, $folderID);

    if (property_exists($presign, 'presignedUrl')) {
      // Post-sign upload.
      $postsign = $this->uploadPresigned($presign->presignedUrl, $file_uri, $file_type);

      if ($postsign['status'] == '200' || $postsign['status'] == '100') {
        // Getting Asset ID.
        $response = $this->uploadConfirmed($presign->processId);
      }
      else {
        // If we got presignedUrl but upload not confirmed, we throw exception.
        throw new UploadAssetException('Failed to upload file after presigning.');
      }
    }
    else {
      // If we couldn't retrieve presignedUrl, we throw exception.
      throw new UploadAssetException('Failed to obtain presigned URL from AWS.');
    }
    return $response;
  }

  /**
   * Get a list of Assets given a Folder ID.
   *
   * @param int $folderId
   *   The webdam folder ID.
   *
   * @param array $params
   *   Additional query parameters for the request.
   *     - sortby: The field to sort by. Options: filename, filesize, datecreated, datemodified. (Default=datecreated)
   *     - sortdir: The direction to sort by. Options: asc, desc (Default=asc)
   *     - limit: The number of items to return. Any int between 1 and 100. (Default=50)
   *     - offset: The item number to start with. (Default=0)
   *     - types: File type filter. Options: image, audiovideo, document, presentation, other. (Default=NULL)
   *
   * @return object
   *   Contains the following keys:
   *     - folders: an array containing a MiniFolder describing $folderId
   *     - offset: The offset used for the query.
   *     - total_count: The total number of assets in the result set across all pages.
   *     - limit: The number of assets returned at a time.
   *     - facets: Information about the assets returned.
   *     - items: an array of Asset objects.
   */
  public function getFolderAssets($folderId, array $params =[]) {
    $this->checkAuth();


    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/folders/' . $folderId . '/assets',
      [
        'headers' => $this->getDefaultHeaders(),
        'query' => $params,
      ]
    );
    $response = json_decode((string) $response->getBody());

    // Replace items key with actual Asset objects.
    $assets = [];
    foreach ($response->items as $asset) {
      $assets[] = Asset::fromJson($asset);
    }
    $response->items = $assets;

    // Replace folders key with actual Folder objects.
    $folders = [];
    if(isset($response->folders) && is_array($response->folders)) {
      foreach ($response->folders as $folder) {
        $folders[] = MiniFolder::fromJson($folder);
      }
    }
    $response->folders = $folders;

    return $response;
  }

  /**
   * Get a list of Assets given an array of Asset ID's.
   *
   * @param array $assetIds
   *   The webdam Asset ID's.
   *
   * @return array
   */
  public function getAssetMultiple(array $assetIds) {
    $this->checkAuth();

    if (empty($assetIds)) {
      return [];
    }

    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/assets/list?ids=' . implode(',',$assetIds),
      ['headers' => $this->getDefaultHeaders()]
    );
    $response = json_decode((string) $response->getBody());
    $assets = [];
    foreach ($response as $asset){
      $assets[] = Asset::fromJson($asset);
    }
    return $assets;
  }

  /**
   * Get a list of Assets given an array of Asset ID's.
   *
   * @param array $params
   *   Additional query parameters for the request.
   *     - sortby: The field to sort by. Options: filename, filesize, datecreated, datemodified. (Default=datecreated)
   *     - sortdir: The direction to sort by. Options: asc, desc (Default=asc)
   *     - limit: The number of items to return. Any int between 1 and 100. (Default=50)
   *     - offset: The item number to start with. (Default=0)
   *     - types: File type filter. Options: image, audiovideo, document, presentation, other. (Default=NULL)
   *
   * @return array
   *
   * @todo clean this up. mystery arrays make an api hard to use.
   */
  public function searchAssets(array $params) {
    $this->checkAuth();

    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/search',
      [
        'headers' => $this->getDefaultHeaders(),
        'query' => $params,
      ]
    );
    $response = json_decode((string) $response->getBody());

    $results = [
      'total_count' => $response->total_count,
    ];
    foreach ($response->items as $asset){
      $results['assets'][] = Asset::fromJson($asset);
    }
    return $results;
  }

  /**
   * Download file asset from webdam
   *
   * @param int $assetID
   *   Asset ID to be fetched
   *
   * @return string
   *   Contents of the file as a string
   */
  public function downloadAsset($assetID) {
    $this->checkAuth();

    $response = $this->client->request(
      "GET",
      $this->baseUrl . '/assets/' . $assetID . '/download',
      [
        'headers' => $this->getDefaultHeaders(),
      ]
    );
    return $response->getBody();
  }

  /**
   * Get asset metadata.
   */
  public function getAssetMetadata($assetId) {
    $this->checkAuth();

    $response = $this->client->request(
      'GET',
      $this->baseUrl . '/assets/' . $assetId . '/metadatas/xmp',
      ['headers' => $this->getDefaultHeaders()]
    );

    $response = json_decode((string) $response->getBody());

    $metadata = [];
    foreach ($response->active_fields as $field) {
      if (!empty($field->value)) {
        $metadata[$field->field] = [
          'label' => $field->field_name,
          'value' => $field->value,
        ];
      }
    }

    return $metadata;
  }

}
