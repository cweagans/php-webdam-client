<?php

/**
 * @file
 * Contains cweagans\webdam\Exception\UploadAssetException.
 */

namespace cweagans\webdam\Exception;

/**
 * Class UploadAssetException
 *
 * This exception is thrown in two cases:
 *   1. The upload failed after getting presign URL. Status code was not 200/100.
 *   2. The upload process failed because we could not retrieve the presign URL.
 *
 * The Exception message will contain details about which scenario is the case.
 */
class UploadAssetException extends \Exception {}