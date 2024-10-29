<?php

/**
 * Class dropbox52ModelBup
 * This class uses only with PHP on 32bit system or with PHP 5.2.x
 * @package Dropbox\Models
 */
class dropbox52ModelBup extends modelBup {

	/**
	 * Key to store token to the session
	 */
	const TOKEN  = 'dropbox_token';

	private $_inFile = NULL;
	private $_chunkSize = 4194304;

	const API_URL     = 'https://api.dropboxapi.com/2/';
	const CONTENT_URL = 'https://content.dropboxapi.com/2/';
	/**
	 * Dummy method
	 * @return bool
	 */
	public function isSupported() { return true; }

	/**
	 * Is authenticated user?
	 * @return bool
	 */
	public function isAuthenticated() {
		if (isset($_SESSION[self::TOKEN]) && !empty($_SESSION[self::TOKEN])) {
			return true;
		}

		if (null !== ($token = $this->readToken())) {
			$_SESSION[self::TOKEN] = $token;
			return true;
		}

		return false;
	}

	/**
	 * Authenticate user
	 * @param string $token Dropbox OAuth2 Token from Authenticator
	 * @return bool
	 */
	public function authenticate($token) {
		if (!$this->isSessionStarted()) {
			session_start();
		}

		$_SESSION[self::TOKEN] = $token;
		$this->saveToken($token);

		return true;
	}

	/**
	 * Get an associative array with dropbox metadata from sandbox
	 * @return array|null
	 */
	public function getUploadedFiles($stacksFolder = '') {

		if (!$this->isAuthenticated()) {
			return null;
		}

		$url = self::API_URL. 'files/list_folder';

		$request = curlBup::createPostRequest(
			$url,
			array(
				"path" => "/backup",
				"recursive" => true,
				"include_media_info" => false,
				"include_deleted" => false,
				"include_has_explicit_shared_members" => false,
				"include_mounted_folders" => true
			),
			true
		);


		$request->setHeader('Content-Type', 'application/json');
		$request->setAuthorization($this->getToken());

		// try {
		$response = json_decode($request->exec(), true);
		// } catch (RuntimeException $e) {
		//   exit (sprintf('Dropbox Client error: %s\nTry to refresh page', $e->getMessage()));
		// }

		if (isset($response['error'])) {
			return null;
		}

		// Formatting uploading data files for use their on backups page
		$files = array();
		$response['entries'] = !empty($response['entries']) ? $response['entries'] : array();
		if(!$stacksFolder && !empty($response['entries']) ) {
				foreach ($response['entries'] as $file) {
				    if ($file['.tag'] == 'folder') {continue;}
					$pathInfo = pathinfo($file['path_lower']);
					$backupInfo = $this->getBackupInfoByFilename($pathInfo['basename']);

					if (!empty($backupInfo['ext']) && $backupInfo['ext'] == 'sql') {
						$files[$backupInfo['id']]['dropbox']['sql'] = $file;
						$files[$backupInfo['id']]['dropbox']['sql']['backupInfo'] = $backupInfo;
						$files[$backupInfo['id']]['dropbox']['sql']['backupInfo'] = dispatcherBup::applyFilters('addInfoIfEncryptedDb', $files[$backupInfo['id']]['dropbox']['sql']['backupInfo']);
					} else {
						$files[$backupInfo['id']]['dropbox']['zip'] = $file;
						$files[$backupInfo['id']]['dropbox']['zip']['backupInfo'] = $backupInfo;
					}
				}
				unset($response['entries']);
				$response['contents'] = $files;
		} else {
			foreach ($response['entries'] as $file) {
				$pathInfo = pathinfo($file['path_lower']);
				$files[] = basename($pathInfo['dirname']) . '/' . basename($file['path_lower']);
			}
			$response = $files;
		}

		return $response;
	}

	/**
	 * Trim the path of forward slashes and replace
	 * consecutive forward slashes with a single slash
	 * @param string $path The path to normalise
	 * @return string
	 */
	private function normalisePath($path)
	{
		$path = preg_replace('#/+#', '/', trim($path, '/'));
		return $path;
	}

	/**
	 * Encode the path, then replace encoded slashes
	 * with literal forward slash characters
	 * @param string $path The path to encode
	 * @return string
	 */
	private function encodePath($path)
	{
		$path = $this->normalisePath($path);
		$path = str_replace('%2F', '/', rawurlencode($path));
		return $path;
	}
	/**
	 * Upload files to Dropbox
	 * @param array $files An array of files to upload
	 * @return int
	 */
	public function upload($files = array(), $stacksFolder = '') {

		// Dropbox max file size limit to upload 150Mb
		$maxSizeFile = 150;

		if (!$this->isAuthenticated()) return 401;
		if (empty($files)) return 404;

		$filepath = $this->getBackupsPath();

		foreach ($files as $file) {

			$fileName = basename($file);
			$fileFullPath = rtrim($filepath, '/') . '/' . $stacksFolder . $fileName;

			$fsize = filesize($fileFullPath);
			$file = fopen($fileFullPath, "rb");

			if (!$file) {
				$this->pushError(sprintf('Failed to read file: %s', $fileName));

				return 500;
			}

			if (file_exists($fileFullPath)) {

				if ($fsize/1024/1024 < $maxSizeFile){
					try{
						$body = fread($file, $fsize);

						$request = curlBup::createPostRequest(
							self::CONTENT_URL . 'files/upload',
							array('file_contents' => $body)
						);

						$args = json_encode(
							array(
								"path"=> '/backup/'. $fileName,
								"mode" => "add",
								"autorename" => false,
								"mute" => false
							)

						);

						$request->setAuthorization($this->getToken());
						$request->setHeader('Content-Type', 'application/octet-stream');
						$request->setHeader('Dropbox-API-Arg', $args);

						$response = json_decode($request->exec(), true);

//						ob_start();
//						var_dump($response);
//						error_log(ob_get_clean());
					} catch (Exception $e) {
						$this->pushError($e->getMessage());

						return 500;
					}
				}else {
					//error_log('To big file');

					$upload = $this->chunkedUpload($fileFullPath);

				}
			} else {
				$this->pushError(sprintf('File not found: %s', $file));
				return 404;
			}
		}
		return 200;

	}

	public function chunkedUpload($file, $filename = false, $path = '', $overwrite = true, $offset = 0, $uploadID = null, $callback = null) {
		$chunkSize = 4194304;
		if (file_exists($file)) {
			if ($handle = @fopen($file, 'r')) {
				// Set initial upload ID and offset
				if ($offset > 0) {
					fseek($handle, $offset);
				}

				/*
					Set firstCommit to true so that the upload session start endpoint is called.
				 */
				$firstCommit = (0 == $offset);

				// Read from the file handle until EOF, uploading each chunk
				while ($data = fread($handle, $chunkSize)) {

					// Set the file, request parameters and send the request

					if ($firstCommit) {

						$request = curlBup::createPostRequest(
							self::CONTENT_URL . 'files/upload_session/start',
							array('file_contents' => $data)
						);

						$args = json_encode(
							array(
								"close" => false,
								'api_v2' => true,
								'content_upload' => true
							)
						);

						$request->setAuthorization($this->getToken());
						$request->setHeader('Content-Type', 'application/octet-stream');
						$request->setHeader('Dropbox-API-Arg', $args);
						$response = json_decode($request->exec(), true);

						$firstCommit = false;
					} else {

						$request = curlBup::createPostRequest(
							self::CONTENT_URL . 'files/upload_session/start',
							array('file_contents' => $data)
						);

						$args = json_encode(
							array(
								'cursor' => array(
									'session_id' => $uploadID,
									// If you send it as a string, Dropbox will be unhappy
									'offset' => (int)$offset
								),
								'api_v2' => true,
								'content_upload' => true
							)
						);

						$request->setAuthorization($this->getToken());
						$request->setHeader('Content-Type', 'application/octet-stream');
						$request->setHeader('Dropbox-API-Arg', $args);

						$response = json_decode($request->exec(), true);

					}

					// On subsequent chunks, use the upload ID returned by the previous request
					if (isset($response['body']->session_id)) {
						$uploadID = $response['body']->session_id;
					}

					/*
						API v2 no longer returns the offset, we need to manually work this out. So check that there are no errors and update the offset as well as calling the callback method.
					 */
					if (!isset($response['body']->error)) {
						$offset = ftell($handle);

					}
				}

				// Complete the chunked upload
				$filename = (is_string($filename)) ? $filename : basename($file);

				$request = curlBup::createPostRequest(
					self::CONTENT_URL . 'files/upload_session/finish'
				);

				$args = json_encode(
					array(
						'cursor' => array(
							'session_id' => $uploadID,
							'offset' => $offset
						),
						'commit' => array(
							'path' => '/backup' . $path . $filename,
							'mode' => 'add'
						),
						'api_v2' => true,
						'content_upload' => true
					)
				);

				$request->setAuthorization($this->getToken());
				$request->setHeader('Content-Type', 'application/octet-stream');
				$request->setHeader('Dropbox-API-Arg', $args);

				$response = json_decode($request->exec(), true);

				return $response;
			} else {
				throw new Exception('Could not open ' . $file . ' for reading');
			}
		}

		// Throw an Exception if the file does not exist
		throw new Exception('Local file ' . $file . ' does not exist');
	}

	/**
	 * Remove file from Dropbox
	 * @param string $filepath Filename with full path to file
	 * @return bool
	 */
	public function remove($filepath) {
		error_log('delete file');
		if (!$this->isAuthenticated()) {
			$this->pushError(__('Authentication required', BUP_LANG_CODE));
			return false;
		}

		$url = self::API_URL. 'files/delete';
		$request = curlBup::createPostRequest($url, array(
//			'root'   => 'sandbox',
			'path'   => $filepath,
//			'locale' => 'en',
		));

		$request->setAuthorization($this->getToken());

		try {
			$response = json_decode($request->exec(), true);
		} catch (RuntimeException $e) {
			$this->pushError($e->getMessage());
			return false;
		}

		if (isset($response['error'])) {
			$this->pushError(implode(':', $response));
			return false;
		}

		return true;
	}

	/**
	 * Download file from Dropbox
	 * @param  string $filename Name of the file to download
	 * @return bool
	 */
	public function download($filename, $returnDataString = false) {
		error_log('download file');
		@set_time_limit(0);
		if (!$this->isAuthenticated()) {
			$this->pushError(__('Authentication required', BUP_LANG_CODE));
			return false;
		}

		if (file_exists($this->getBackupsPath() . $filename)) {
			return $returnDataString ? file_get_contents($this->getBackupsPath() . $filename) : true;
		}

		$url = self::CONTENT_URL. 'files/download';
		$request = curlBup::createGetRequest(
			$url . $this->getDropboxPath() . ltrim($filename, '/')
		);
		$request->setAuthorization($_SESSION[self::TOKEN]);

		try {
			$response = $request->exec();
		} catch (RuntimeException $e) {
			$this->pushError($e->getMessage());
		}

		if($returnDataString)
			return $response;

		if (!file_put_contents($this->getBackupsPath() . $filename, $response)) {
			$this->pushError(__(sprintf('Can\'t download the file: %', $filename), BUP_LANG_CODE));
			return false;
		}

		return true;
	}

	/**
	 * Dummy method
	 * @return array
	 */
	public function getQuota() {
		return array();
	}

	/**
	 * Is session already started?
	 * @return bool
	 */
	public function isSessionStarted() {
		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			return session_status() === PHP_SESSION_NONE ? false : true;
		} else {
			return session_id() === '' ? false : true;
		}
	}

	/**
	 * Get path to the backups
	 * @return string
	 */
	public function getBackupsPath() {
		return frameBup::_()->getModule('warehouse')->getPath()
			. DIRECTORY_SEPARATOR;

	}

	public function removeToken()
	{
		$storage = frameBup::_()->getModule('warehouse')->getPath();

		if (false !== $expired = glob($storage . '/dropbox*.json')) {
			if (is_array($expired) && count($expired) > 0) {
				foreach ($expired as $file) {
					@unlink($file);
				}
			}
		}
	}

	/**
	 * Saves the token
	 *
	 * @param string $token
	 */
	protected function saveToken($token)
	{
		$storage = frameBup::_()->getModule('warehouse')->getPath();

		$this->removeToken();

		file_put_contents($storage . '/' . uniqid('dropbox') . '.json', $token);
	}

	/**
	 * Reads the token
	 */
	protected function readToken()
	{
		$storage = frameBup::_()->getModule('warehouse')->getPath();

		if (false !== $token = glob($storage . '/dropbox*.json')) {
			if (is_array($token) && count($token) === 1) {
				return file_get_contents($token[0]);
			}
		}

		return null;
	}

	protected function getDomainName()
	{
		return parse_url(get_bloginfo('wpurl'), PHP_URL_HOST);
	}

	protected function getDropboxPath()
	{
		return '/' . $this->getDomainName() . '/';
	}
	protected function getToken()
	{
		if(isset($_SESSION[self::TOKEN]) && !empty($_SESSION[self::TOKEN])) {
			return $_SESSION[self::TOKEN];
		}

		if (null !== ($token = $this->readToken())) {
			return $token;
		}

		return false;
	}
	public function isUserAuthorizedInService($destination = null)
	{
		$isAuthorized = $this->isAuthenticated() ? true : false;
		if(!$isAuthorized)
			$this->pushError($this->backupPlaceAuthErrorMsg . 'DropBox!');
		return $isAuthorized;
	}
}
