<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// Check for the json extension, the Google APIs PHP Client won't function
// without it.
if (! function_exists('json_decode')) {
  throw new Exception('Google PHP API Client requires the JSON PHP extension');
}

if (! function_exists('http_build_query')) {
  throw new Exception('Google PHP API Client requires http_build_query()');
}

if (! ini_get('date.timezone') && function_exists('date_default_timezone_set')) {
  date_default_timezone_set('UTC');
}

// hack around with the include paths a bit so the library 'just works'
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());

require_once "config.php";
// If a local configuration file is found, merge it's values with the default configuration
if (file_exists(dirname(__FILE__)  . '/local_config.php')) {
  $defaultConfig = $apiConfig;
  require_once (dirname(__FILE__)  . '/local_config.php');
  $apiConfig = array_merge($defaultConfig, $apiConfig);
}

// Include the top level classes, they each include their own dependencies
require_once 'service/Google_Model.php';
require_once 'service/Google_Service.php';
require_once 'service/Google_ServiceResource.php';
require_once 'auth/Google_AssertionCredentials.php';
require_once 'auth/Google_Signer.php';
require_once 'auth/Google_P12Signer.php';
require_once 'service/Google_BatchRequest.php';
require_once 'external/URITemplateParser.php';
require_once 'auth/Google_Auth.php';
require_once 'cache/Google_Cache.php';
require_once 'io/Google_IO.php';
require_once('service/Google_MediaFileUpload.php');

if(!class_exists('Google_Client')){
	require_once 'lib/GoogleClient.php';
}

// Exceptions that the Google PHP API Library can throw
class Google_Exception extends Exception {}
class Google_AuthException extends Google_Exception {}
class Google_CacheException extends Google_Exception {}
class Google_IOException extends Google_Exception {}
class Google_ServiceException extends Google_Exception {
  /**
   * Optional list of errors returned in a JSON body of an HTTP error response.
   */
  protected $errors = array();

  /**
   * Override default constructor to add ability to set $errors.
   *
   * @param string $message
   * @param int $code
   * @param Exception|null $previous
   * @param [{string, string}] errors List of errors returned in an HTTP
   * response.  Defaults to [].
   */
  public function __construct($message, $code = 0, Exception $previous = null,
                              $errors = array()) {
    if(version_compare(PHP_VERSION, '5.3.0') >= 0) {
      parent::__construct($message, $code, $previous);
    } else {
      parent::__construct($message, $code);
    }

    $this->errors = $errors;
  }

  /**
   * An example of the possible errors returned.
   *
   * {
   *   "domain": "global",
   *   "reason": "authError",
   *   "message": "Invalid Credentials",
   *   "locationType": "header",
   *   "location": "Authorization",
   * }
   *
   * @return [{string, string}] List of errors return in an HTTP response or [].
   */
  public function getErrors() {
    return $this->errors;
  }
}
