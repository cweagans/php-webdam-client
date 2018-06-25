<?php

/**
 * @file
 * Provides API interface for Webdam.
 */

namespace cweagans\webdam;

use cweagans\webdam\Entity\Asset;
use cweagans\webdam\Entity\Folder;
use cweagans\webdam\Entity\MiniFolder;
use cweagans\webdam\Exception\InvalidCredentialsException;
use cweagans\webdam\Exception\UploadAssetException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;

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

  /** @var string Contains the refresh token necessary to renew connections. */
  protected $refreshToken;

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
   * Authenticates a user.
   *
   * @param array $data
   *   An array of API parameters to pass. Defaults to password based
   *   authentication information.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \cweagans\webdam\Exception\InvalidCredentialsException
   */
  public function authenticate(array $data = []) {
    $url = $this->baseUrl . '/oauth2/token';
    if (empty($data)) {
      $data = [
        'grant_type' => 'password',
        'username' => $this->username,
        'password' => $this->password,
        'client_id' => $this->clientId,
        'client_secret' => $this->clientSecret,
      ];
    }

    /**
     * For error response body details:
     *
     * @see \cweagans\webdam\tests\ClientTest::testInvalidClient()
     * @see \cweagans\webdam\tests\ClientTest::testInvalidGrant()
     *
     * For successful auth response body details:
     * @see \cweagans\webdam\tests\ClientTest::testSuccessfulAuthentication()
     */
    try {
      $response = $this->client->request('POST', $url, ['form_params' => $data]);

      // Body properties: access_token, expires_in, token_type, refresh_token
      $body = (string) $response->getBody();
      $body = json_decode($body);

      $this->accessToken = $body->access_token;
      $this->accessTokenExpiry = time() + $body->expires_in;
      // We should only get an initial refresh_token and reuse it after the
      // first session. The access_token gets replaced instead of a new
      // refresh_token.
      $this->refreshToken = !empty($body->refresh_token) ?
        $body->refresh_token :
        $this->refreshToken;
    } catch (ClientException $e) {
      // Looks like any form of bad auth with Webdam is a 400, but we're wrapping
      // it here just in case.
      if ($e->getResponse()->getStatusCode() == 400) {
        $body = (string) $e->getResponse()->getBody();
        $body = json_decode($body);

        throw new InvalidCredentialsException(sprintf('%s (%s).', $body->error_description, $body->error));
      }
    }
  }

  /**
   * Authenticates with the DAM service and retrieves or reuses an access token.
   *
   * {@inheritdoc}
   *
   * @return array
   *   An array of authentication token information.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \cweagans\webdam\Exception\InvalidCredentialsException
   *
   * @see \Drupal\media_acquiadam\Client::getAuthState()
   */
  public function checkAuth() {
    /** @var bool TRUE if the access token expiration time has elapsed. */
    $is_expired_token = empty($this->accessTokenExpiry) || time() >= $this->accessTokenExpiry;
    /** @var bool $is_expired_session TRUE if the session has expired. */
    $is_expired_session = !empty($this->accessToken) && $is_expired_token;

    // Session is still valid.
    if (!empty($this->accessToken) && !$is_expired_token) {
      return $this->getAuthState();
    }

    // Session has expired but we have a refresh token.
    elseif ($is_expired_session && !empty($this->refreshToken)) {
      $data = [
        'grant_type' => 'refresh_token',
        'refresh_token' => $this->refreshToken,
        'client_id' => $this->clientId,
        'client_secret' => $this->clientSecret,
      ];
      $this->authenticate($data);
    }
    // Session was manually set so we don't do anything.
    // Adding an $is_expired_session condition here allows the DAM browser to
    // fall back to the global account.
    elseif ($this->manualToken) {
      // @TODO: Why can't we authenticate after a manual set?
      throw new InvalidCredentialsException('Cannot reauthenticate a manually set token.');
    }
    // Expired or new session.
    else {
      $this->authenticate();
    }

    return $this->getAuthState();
  }

  /**
   * Set the internal auth token.
   *
   * @param string $token
   * @param int $token_expiry
   * @param string $refresh_token The authentication refresh token.
   */
  public function setToken($token, $token_expiry, $refresh_token = NULL) {
    $this->manualToken = TRUE;
    $this->accessToken = $token;
    $this->accessTokenExpiry = $token_expiry;
    $this->refreshToken = $refresh_token;
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
   *
   * @return array
   *   An array of authentication state information including:
   *     @bool valid_token If the access token is valid.
   *     @string access_token The access token.
   *     @int access_token_expiry The time when the access token expires.
   *     @string refresh_token The refresh token used to refresh authentication.
   */
  public function getAuthState() {
    $state = ['valid_token' => FALSE];

    if (!is_null($this->accessToken) && time() < $this->accessTokenExpiry) {
      $state = [
        'valid_token' => TRUE,
        'access_token' => $this->accessToken,
        'access_token_expiry' => $this->accessTokenExpiry,
      ];
    }

    if (!empty($state['valid_token']) && empty($state['refresh_token'])) {
      $state['refresh_token'] = $this->refreshToken;
    }

    return $state;
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
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \cweagans\webdam\Exception\InvalidCredentialsException
   */
  protected function uploadPresigned($presignedUrl, $file_uri, $file_type) {
    $this->checkAuth();

    $file = fopen($file_uri, 'r');
    $response = $this->client->request(
      'PUT',
      $presignedUrl, [
        'headers' => ['Content-Type' => $file_type],
        'body' => stream_get_contents($file),
        RequestOptions::TIMEOUT => 0,
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

  /**
   * Get a list of metadata.
   *
   * @return array
   *   A list of active xmp metadata fields.
   */
  public function getActiveXmpFields() {
    $this->checkAuth();

    $response = $this->client->request(
      'GET',
      $this->baseUrl . '/metadataschemas/xmp?full=1',
      ['headers' => $this->getDefaultHeaders()]
    );

    $response = json_decode((string) $response->getBody());

    $metadata = [];
    foreach ($response->xmpschema as $field) {
      if ($field->status == 'active') {
        $metadata['xmp_' . strtolower($field->field)] = [
          'name' => $field->name,
          'label' => $field->label,
          'type' => $field->type,
        ];
      }
    }

    return $metadata;
  }

  /**
   * Queue custom asset conversions for download.
   *
   * This is a 2 step process:
   *   1. Queue assets.
   *   2. Download from queue.
   *
   * This step will allow users to queue an asset for download by specifying an
   * AssetID and a Preset ID or custom conversion parameters. If a valid
   * PresetID is defined, the other conversions parameters will be ignored
   * (format, resolution, size, orientation, colorspace).
   *
   * @param array|int $assetIDs
   *   A single or list of asset IDs.
   * @param array $options
   *   Asset preset or conversion options.
   *
   * @return array
   *   An array of response data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \cweagans\webdam\Exception\InvalidCredentialsException
   */
  public function queueAssetDownload($assetIDs, array $options) {
    $this->checkAuth();

    if (!is_array($assetIDs)) {
      $assetIDs = [$assetIDs];
    }

    $data = ['items' => []];
    foreach ($assetIDs as $assetID) {
      $data['items'][] = ['id' => $assetID] + $options;
    }

    $response = $this->client->request(
      'POST',
      $this->baseUrl . '/assets/queuedownload',
      [
        'headers' => $this->getDefaultHeaders(),
        RequestOptions::JSON => $data,
      ]
    );
    $response = json_decode((string) $response->getBody(), TRUE);

    return $response;
  }

  /**
   * Gets asset download queue information.
   *
   * This is a 2 step process:
   *   1. Queue assets.
   *   2. Download from queue.
   *
   * This step will allow users to download the queued asset using the download
   * key returned from step1 (Queue asset process). The output of this step will
   * be a download URL to the asset or the download status, if the asset is not
   * ready for download.
   *
   * @param string $downloadKey
   *   The download key to check the status of.
   *
   * @return array
   *   An array of response data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \cweagans\webdam\Exception\InvalidCredentialsException
   */
  public function downloadFromQueue($downloadKey) {
    $this->checkAuth();

    $response = $this->client->request(
      'GET',
      sprintf('%s/downloadfromqueue/%s', $this->baseUrl, $downloadKey),
      ['headers' => $this->getDefaultHeaders()]
    );

    $response = json_decode((string) $response->getBody(), TRUE);

    return $response;
  }

  /**
   * Edit an asset.
   *
   * If an asset is uploaded and its required fields are not filled in, the
   * asset is in onhold status and cannot be activated until all required fields
   * are supplied. Any attempt to change the status to 'active' for assets that
   * still require metadata will return back 409.
   *
   * @param int $assetID
   *   The asset to edit.
   * @param array $data
   *   An array of values to set.
   *    filename       string  The new filename for the asset.
   *    status         string  The new status of the asset. Either active or
   *                           inactive.
   *    name           string  The new name for the asset.
   *    description    string  The new description of the asset.
   *    folder         long    The id of the folder to move asset to.
   *    thumbnail_ttl  string  Time to live for thumbnails
   *                             Default: Set by the account admin
   *                             Values: '+3 min', '+15 min', '+2 hours',
   *                             '+1 day', '+2 weeks', 'no-expiration'.
   *
   * @return \cweagans\webdam\Entity\Asset|bool
   *   An asset object on success, or FALSE on failure.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \cweagans\webdam\Exception\InvalidCredentialsException
   */
  public function editAsset($assetID, array $data) {
    $this->checkAuth();

    $response = $this->client->request(
      'PUT',
      sprintf('%s/assets/%d', $this->baseUrl, $assetID),
      [
        'headers' => $this->getDefaultHeaders(),
        RequestOptions::JSON => $data,
      ]
    );

    if (409 == $response->getStatusCode()) {
      return FALSE;
    }

    $asset = Asset::fromJson((string) $response->getBody());

    return $asset;
  }

  /**
   * Edit asset XMP metadata.
   *
   * @param int $assetID
   *   The asset to edit XMP metadata for.
   * @param array $data
   *   A key value array of metadata to edit.
   *
   * @return array
   *   The metadata of the asset.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \cweagans\webdam\Exception\InvalidCredentialsException
   */
  public function editAssetXmpMetadata($assetID, array $data) {
    $this->checkAuth();

    $data['type'] = 'assetxmp';

    $response = $this->client->request(
      'PUT',
      sprintf('%s/assets/%d/metadatas/xmp', $this->baseUrl, $assetID),
      [
        'headers' => $this->getDefaultHeaders(),
        RequestOptions::JSON => $data,
      ]
    );

    $response = json_decode((string) $response->getBody(), TRUE);

    return $response;
  }

}
