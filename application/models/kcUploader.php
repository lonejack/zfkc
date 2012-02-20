<?php
class Application_Model_kcUploader {

	/** Release version */
	const VERSION = "2.51";

	/** Config session-overrided settings
	 * @var array */
	public static $config = array();

	/** Opener applocation properties
	 *   $opener['name']                 Got from $_GET['opener'];
	 *   $opener['CKEditor']['funcNum']  CKEditor function number (got from $_GET)
	 *   $opener['TinyMCE']              Boolean
	 * @var array */
	protected $opener = array();

	/** Got from $_GET['type'] or first one $config['types'] array key, if inexistant
	 * @var string */
	protected $type;

	/** Helper property. Local filesystem path to the Type Directory
	 * Equivalent: $config['uploadDir'] . "/" . $type
	 * @var string */
	protected $typeDir;

	/** Helper property. Web URL to the Type Directory
	 * Equivalent: $config['uploadURL'] . "/" . $type
	 * @var string */
	protected $typeURL;

	/** Linked to $config['types']
	 * @var array */
	protected $types = array();

	/** Settings which can override default settings if exists as keys in $config['types'][$type] array
	 * @var array */
	protected $typeSettings = array('disabled', 'theme', 'dirPerms', 'filePerms', 'denyZipDownload', 'maxImageWidth', 'maxImageHeight', 'thumbWidth', 'thumbHeight', 'jpegQuality', 'access', 'filenameChangeChars', 'dirnameChangeChars', 'denyExtensionRename', 'deniedExts');

	/** Got from language file
	 * @var string */
	protected $charset;

	/** The language got from $_GET['lng'] or $_GET['lang'] or... Please see next property
	 * @var string */
	protected $lang = 'en';

	/** Possible language $_GET keys
	 * @var array */
	protected $langInputNames = array('lang', 'langCode', 'lng', 'language', 'lang_code');

	/** Uploaded file(s) info. Linked to first $_FILES element
	 * @var array */
	protected $file;

	/** Next three properties are got from the current language file
	 * @var string */
	protected $dateTimeFull;   // Currently not used
	protected $dateTimeMid;    // Currently not used
	protected $dateTimeSmall;

	/** Contain Specified language labels
	 * @var array */
	protected $labels = array();

	/** Contain unprocessed $_GET array. Please use this instead of $_GET
	 * @var array */
	protected $get;

	/** Contain unprocessed $_POST array. Please use this instead of $_POST
	 * @var array */
	protected $post;

	/** Contain unprocessed $_COOKIE array. Please use this instead of $_COOKIE
	 * @var array */
	protected $cookie;

	/** Session array. Please use this property instead of $_SESSION
	 * @var array */
	protected $session;

	/** CMS integration attribute (got from $_GET['cms'])
	 * @var string */
	protected $cms = "";

	/** Magic method which allows read-only access to protected or private class properties
	 * @param string $property
	 * @return mixed */
	public function __get($property) {
		return property_exists($this, $property) ? $this->$property : null;
	}

	public function __construct(Zend_Config $conf, $options ) {
		$config = $conf->toArray();
		// DISABLE MAGIC QUOTES
		if (function_exists('set_magic_quotes_runtime'))
		@set_magic_quotes_runtime(false);

		// INPUT INIT
		$input = new Admin_Model_KCFinderInput();
		$this->get = &$input->get;
		$this->post = &$input->post;
		$this->cookie = &$input->cookie;

		// SET CMS INTEGRATION ATTRIBUTE
		if (isset($this->get['cms']) && in_array($this->get['cms'], array("drupal")))
		{
			$this->cms = $this->get['cms'];
		}

		// LINKING UPLOADED FILE
		if (count($_FILES))
		$this->file = &$_FILES[key($_FILES)];

		// LOAD DEFAULT CONFIGURATION
		//require "config.php";

		// SETTING UP SESSION(done!)
		if(0)
		{
			if (isset($config['_sessionLifetime']))
			ini_set('session.gc_maxlifetime', $config['_sessionLifetime'] * 60);
			if (isset($config['_sessionDir']))
			ini_set('session.save_path', $config['_sessionDir']);
			if (isset($config['_sessionDomain']))
			ini_set('session.cookie_domain', $config['_sessionDomain']);
			if( !isset($options['session_started'] ) && !$options['session_started'] )
			{
				switch ($this->cms ) {
					case "drupal": break;
					default: session_start(); break;
				}

			}
		}
		
		// RELOAD DEFAULT CONFIGURATION
		//		require "config.php";
		$this->config = $config;

		// LOAD SESSION CONFIGURATION IF EXISTS
		if (isset($config['_sessionVar']) &&
		is_array($config['_sessionVar'])
		) {
			foreach ($config['_sessionVar'] as $key => $val)
			if ((substr($key, 0, 1) != "_") && isset($config[$key]))
			$this->config[$key] = $val;
			if (!isset($this->config['_sessionVar']['self']))
			$this->config['_sessionVar']['self'] = array();
			$this->session = &$this->config['_sessionVar']['self'];
		} else
		$this->session = &$_SESSION;

		// GET TYPE DIRECTORY
		$this->types = &$this->config['types'];
		$firstType = array_keys($this->types);
		$firstType = $firstType[0];
		$this->type = (
		isset($this->get['type']) &&
		isset($this->types[$this->get['type']])
		)
		? $this->get['type'] : $firstType;

		// LOAD TYPE DIRECTORY SPECIFIC CONFIGURATION IF EXISTS
		if (is_array($this->types[$this->type])) {
			foreach ($this->types[$this->type] as $key => $val)
			if (in_array($key, $this->typeSettings))
			$this->config[$key] = $val;
			$this->types[$this->type] = isset($this->types[$this->type]['type'])
			? $this->types[$this->type]['type'] : "";
		}

		// COOKIES INIT
		$ip = '(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)';
		$ip = '/^' . implode('\.', array($ip, $ip, $ip, $ip)) . '$/';
		if (preg_match($ip, $_SERVER['HTTP_HOST']) ||
		preg_match('/^[^\.]+$/', $_SERVER['HTTP_HOST'])
		)
		$this->config['cookieDomain'] = "";
		elseif (!strlen($this->config['cookieDomain']))
		$this->config['cookieDomain'] = $_SERVER['HTTP_HOST'];
		if (!strlen($this->config['cookiePath']))
		$this->config['cookiePath'] = "/";

		// UPLOAD FOLDER INIT

		// FULL URL
		if (preg_match('/^([a-z]+)\:\/\/([^\/^\:]+)(\:(\d+))?\/(.+)\/?$/',
		$this->config['uploadURL'], $patt)
		) {
			list($unused, $protocol, $domain, $unused, $port, $path) = $patt;
			$path = Application_Model_kclib_Path::normalize($path);
			$this->config['uploadURL'] = "$protocol://$domain" . (strlen($port) ? ":$port" : "") . "/$path";
			$this->config['uploadDir'] = strlen($this->config['uploadDir'])
			? Application_Model_kclib_Path::normalize($this->config['uploadDir'])
			: Application_Model_kclib_Path::url2fullPath("/$path");
			$this->typeDir = "{$this->config['uploadDir']}/{$this->type}";
			$this->typeURL = "{$this->config['uploadURL']}/{$this->type}";

			// SITE ROOT
		} elseif ($this->config['uploadURL'] == "/") {
			$this->config['uploadDir'] = strlen($this->config['uploadDir'])
			? Application_Model_kclib_Path::normalize($this->config['uploadDir'])
			: Application_Model_kclib_Path::normalize($_SERVER['DOCUMENT_ROOT']);
			$this->typeDir = "{$this->config['uploadDir']}/{$this->type}";
			$this->typeURL = "/{$this->type}";

			// ABSOLUTE & RELATIVE
		} else {
			$this->config['uploadURL'] = (substr($this->config['uploadURL'], 0, 1) === "/")
			? Application_Model_kclib_Path::normalize($this->config['uploadURL'])
			: Application_Model_kclib_Path::rel2abs_url($this->config['uploadURL']);
			$this->config['uploadDir'] = strlen($this->config['uploadDir'])
			? Application_Model_kclib_Path::normalize($this->config['uploadDir'])
			: Application_Model_kclib_Path::url2fullPath($this->config['uploadURL']);
			$this->typeDir = "{$this->config['uploadDir']}/{$this->type}";
			$this->typeURL = "{$this->config['uploadURL']}/{$this->type}";
		}
		if (!is_dir($this->config['uploadDir']))
		@mkdir($this->config['uploadDir'], $this->config['dirPerms']);

		// HOST APPLICATIONS INIT
		if (isset($this->get['CKEditorFuncNum']))
		$this->opener['CKEditor']['funcNum'] = $this->get['CKEditorFuncNum'];
		if (isset($this->get['opener']) &&
		(strtolower($this->get['opener']) == "tinymce") &&
		isset($this->config['_tinyMCEPath']) &&
		strlen($this->config['_tinyMCEPath'])
		)
		$this->opener['TinyMCE'] = true;

		// LOCALIZATION
		foreach ($this->langInputNames as $key)
		if (isset($this->get[$key]) &&
		preg_match('/^[a-z][a-z\._\-]*$/i', $this->get[$key]) &&
		file_exists("lang/" . strtolower($this->get[$key]) . ".php")
		) {
			$this->lang = $this->get[$key];
			break;
		}
		$this->localize($this->lang);

		// CHECK & MAKE DEFAULT .htaccess
		if (isset($this->config['_check4htaccess']) &&
		$this->config['_check4htaccess']
		) {
			$htaccess = "{$this->config['uploadDir']}/.htaccess";
			if (!file_exists($htaccess)) {
				if (!@file_put_contents($htaccess, $this->get_htaccess()))
				$this->backMsg("Cannot write to upload folder. {$this->config['uploadDir']}");
			} else {
				if (false === ($data = @file_get_contents($htaccess)))
				$this->backMsg("Cannot read .htaccess");
				if (($data != $this->get_htaccess()) && !@file_put_contents($htaccess, $data))
				$this->backMsg("Incorrect .htaccess file. Cannot rewrite it!");
			}
		}

		// CHECK & CREATE UPLOAD FOLDER
		if (!is_dir($this->typeDir)) {
			if (!mkdir($this->typeDir, $this->config['dirPerms']))
			$this->backMsg("Cannot create {dir} folder.", array('dir' => $this->type));
		}
		elseif (!is_readable($this->typeDir))
		{
			$this->backMsg("Cannot read upload folder.");
		}
	}

	public function upload() {
		$config = &$this->config;
		$file = &$this->file;
		$url = $message = "";

		if ($config['disabled'] || !$config['access']['files']['upload']) {
			if (isset($file['tmp_name'])) @unlink($file['tmp_name']);
			$message = $this->label("You don't have permissions to upload files.");

		} elseif (true === ($message = $this->checkUploadedFile())) {
			$message = "";

			$dir = "{$this->typeDir}/";
			if (isset($this->get['dir']) &&
			(false !== ($gdir = $this->checkInputDir($this->get['dir'])))
			) {
				$udir = Application_Model_kclib_Path::normalize("$dir$gdir");
				if (substr($udir, 0, strlen($dir)) !== $dir)
				$message = $this->label("Unknown error.");
				else {
					$l = strlen($dir);
					$dir = "$udir/";
					$udir = substr($udir, $l);
				}
			}

			if (!strlen($message)) {
				if (!is_dir(Application_Model_kclib_Path::normalize($dir)))
				@mkdir(Application_Model_kclib_Path::normalize($dir), $this->config['dirPerms'], true);

				$filename = $this->normalizeFilename($file['name']);
				$target = Application_Model_kclib_File::getInexistantFilename($dir . $filename);

				if (!@move_uploaded_file($file['tmp_name'], $target) &&
				!@rename($file['tmp_name'], $target) &&
				!@copy($file['tmp_name'], $target)
				)
				$message = $this->label("Cannot move uploaded file to target folder.");
				else {
					if (function_exists('chmod'))
					@chmod($target, $this->config['filePerms']);
					$this->makeThumb($target);
					$url = $this->typeURL;
					if (isset($udir)) $url .= "/$udir";
					$url .= "/" . basename($target);
					if (preg_match('/^([a-z]+)\:\/\/([^\/^\:]+)(\:(\d+))?\/(.+)$/', $url, $patt)) {
						list($unused, $protocol, $domain, $unused, $port, $path) = $patt;
						$base = "$protocol://$domain" . (strlen($port) ? ":$port" : "") . "/";
						$url = $base . Application_Model_kclib_Path::urlPathEncode($path);
					} else
					$url = Application_Model_kclib_Path::urlPathEncode($url);
				}
			}
		}

		if (strlen($message) &&
		isset($this->file['tmp_name']) &&
		file_exists($this->file['tmp_name'])
		)
		@unlink($this->file['tmp_name']);

		if (strlen($message) && method_exists($this, 'errorMsg'))
		$this->errorMsg($message);
		$this->callBack($url, $message);
	}

	protected function normalizeFilename($filename) {
		if (isset($this->config['filenameChangeChars']) &&
		is_array($this->config['filenameChangeChars'])
		)
		$filename = strtr($filename, $this->config['filenameChangeChars']);
		return $filename;
	}

	protected function normalizeDirname($dirname) {
		if (isset($this->config['dirnameChangeChars']) &&
		is_array($this->config['dirnameChangeChars'])
		)
		$dirname = strtr($dirname, $this->config['dirnameChangeChars']);
		return $dirname;
	}

	protected function checkUploadedFile(array $aFile=null) {
		$config = &$this->config;
		$file = ($aFile === null) ? $this->file : $aFile;

		if (!is_array($file) || !isset($file['name']))
		return $this->label("Unknown error");

		if (is_array($file['name'])) {
			foreach ($file['name'] as $i => $name) {
				$return = $this->checkUploadedFile(array(
	                    'name' => $name,
	                    'tmp_name' => $file['tmp_name'][$i],
	                    'error' => $file['error'][$i]
				));
				if ($return !== true)
				return "$name: $return";
			}
			return true;
		}

		$extension = Application_Model_kclib_File::getExtension($file['name']);
		$typePatt = strtolower(Application_Model_kclib_Text::clearWhitespaces($this->types[$this->type]));

		// CHECK FOR UPLOAD ERRORS
		if ($file['error'])
		return
		($file['error'] == UPLOAD_ERR_INI_SIZE) ?
		$this->label("The uploaded file exceeds {size} bytes.",
		array('size' => ini_get('upload_max_filesize'))) : (
		($file['error'] == UPLOAD_ERR_FORM_SIZE) ?
		$this->label("The uploaded file exceeds {size} bytes.",
		array('size' => $this->get['MAX_FILE_SIZE'])) : (
		($file['error'] == UPLOAD_ERR_PARTIAL) ?
		$this->label("The uploaded file was only partially uploaded.") : (
		($file['error'] == UPLOAD_ERR_NO_FILE) ?
		$this->label("No file was uploaded.") : (
		($file['error'] == UPLOAD_ERR_NO_TMP_DIR) ?
		$this->label("Missing a temporary folder.") : (
		($file['error'] == UPLOAD_ERR_CANT_WRITE) ?
		$this->label("Failed to write file.") :
		$this->label("Unknown error.")
		)))));

		// HIDDEN FILENAMES CHECK
		elseif (substr($file['name'], 0, 1) == ".")
		return $this->label("File name shouldn't begins with '.'");

		// EXTENSION CHECK
		elseif (!$this->validateExtension($extension, $this->type))
		return $this->label("Denied file extension.");

		// SPECIAL DIRECTORY TYPES CHECK (e.g. *img)
		elseif (preg_match('/^\*([^ ]+)(.*)?$/s', $typePatt, $patt)) {
			list($typePatt, $type, $params) = $patt;
			if (class_exists("type_$type")) {
				$class = "type_$type";
				$type = new $class();
				$cfg = $config;
				$cfg['filename'] = $file['name'];
				if (strlen($params))
				$cfg['params'] = trim($params);
				$response = $type->checkFile($file['tmp_name'], $cfg);
				if ($response !== true)
				return $this->label($response);
			} else
			return $this->label("Non-existing directory type.");
		}

		// IMAGE RESIZE
		$gd = new Application_Model_kclib_Gd($file['tmp_name']);
		if (!$gd->init_error && !$this->imageResize($gd, $file['tmp_name']))
		return $this->label("The image is too big and/or cannot be resized.");

		return true;
	}

	protected function checkInputDir($dir, $inclType=true, $existing=true) {
		$dir = Application_Model_kclib_Path::normalize($dir);
		if (substr($dir, 0, 1) == "/")
		$dir = substr($dir, 1);

		if ((substr($dir, 0, 1) == ".") || (substr(basename($dir), 0, 1) == "."))
		return false;

		if ($inclType) {
			$first = explode("/", $dir);
			$first = $first[0];
			if ($first != $this->type)
			return false;
			$return = $this->removeTypeFromPath($dir);
		} else {
			$return = $dir;
			$dir = "{$this->type}/$dir";
		}

		if (!$existing)
		return $return;

		$path = "{$this->config['uploadDir']}/$dir";
		return (is_dir($path) && is_readable($path)) ? $return : false;
	}

	protected function validateExtension($ext, $type) {
		$ext = trim(strtolower($ext));
		if (!isset($this->types[$type]))
		return false;

		$exts = strtolower(Application_Model_kclib_Text::clearWhitespaces($this->config['deniedExts']));
		if (strlen($exts)) {
			$exts = explode(" ", $exts);
			if (in_array($ext, $exts))
			return false;
		}

		$exts = trim($this->types[$type]);
		if (!strlen($exts) || substr($exts, 0, 1) == "*")
		return true;

		if (substr($exts, 0, 1) == "!") {
			$exts = explode(" ", trim(strtolower(substr($exts, 1))));
			return !in_array($ext, $exts);
		}

		$exts = explode(" ", trim(strtolower($exts)));
		return in_array($ext, $exts);
	}

	protected function getTypeFromPath($path) {
		return preg_match('/^([^\/]*)\/.*$/', $path, $patt)
		? $patt[1] : $path;
	}

	protected function removeTypeFromPath($path) {
		return preg_match('/^[^\/]*\/(.*)$/', $path, $patt)
		? $patt[1] : "";
	}

	protected function imageResize($image, $file=null) {
		if (!($image instanceof gd)) {
			$gd = new Application_Model_kclib_Gd($image);
			if ($gd->init_error) return false;
			$file = $image;
		} elseif ($file === null)
		return false;
		else
		$gd = $image;

		if ((!$this->config['maxImageWidth'] && !$this->config['maxImageHeight']) ||
		(
		($gd->get_width() <= $this->config['maxImageWidth']) &&
		($gd->get_height() <= $this->config['maxImageHeight'])
		)
		)
		return true;

		if ((!$this->config['maxImageWidth'] || !$this->config['maxImageHeight'])) {
			if ($this->config['maxImageWidth']) {
				if ($this->config['maxImageWidth'] >= $gd->get_width())
				return true;
				$width = $this->config['maxImageWidth'];
				$height = $gd->get_prop_height($width);
			} else {
				if ($this->config['maxImageHeight'] >= $gd->get_height())
				return true;
				$height = $this->config['maxImageHeight'];
				$width = $gd->get_prop_width($height);
			}
			if (!$gd->resize($width, $height))
			return false;

		} elseif (!$gd->resize_fit(
		$this->config['maxImageWidth'], $this->config['maxImageHeight']
		))
		return false;

		return $gd->imagejpeg($file, $this->config['jpegQuality']);
	}

	static function makeThumb1($file, $overwrite=true) {
		$gd = new Application_Model_kclib_Gd($file);

		// Drop files which are not GD handled images
		if ($gd->init_error)
		return true;

		$thumb = substr($file, strlen($this->config['uploadDir']));
		$thumb = $this->config['uploadDir'] . "/" . $this->config['thumbsDir'] . "/" . $thumb;
		$thumb = Application_Model_kclib_Path::normalize($thumb);
		$thumbDir = dirname($thumb);
		if (!is_dir($thumbDir) && !@mkdir($thumbDir, $this->config['dirPerms'], true))
		return false;

		if (!$overwrite && is_file($thumb))
		return true;

		// Images with smaller resolutions than thumbnails
		if (($gd->get_width() <= $this->config['thumbWidth']) &&
		($gd->get_height() <= $this->config['thumbHeight'])
		) {
			$browsable = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
			// Drop only browsable types
			if (in_array($gd->type, $browsable))
			return true;

			// Resize image
		} elseif (!$gd->resize_fit($this->config['thumbWidth'], $this->config['thumbHeight']))
		return false;

		// Save thumbnail
		return $gd->imagejpeg($thumb, $this->config['jpegQuality']);
	}

	protected function localize($langCode) {
		$langFile = realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR."Language".DIRECTORY_SEPARATOR.$langCode.".php";
		include_once $langFile;
		$lClass = new Admin_Model_Language();
		$lang = &$lClass->lang;

		setlocale(LC_ALL, $lang['_locale']);
		$this->charset = $lang['_charset'];
		$this->dateTimeFull = $lang['_dateTimeFull'];
		$this->dateTimeMid = $lang['_dateTimeMid'];
		$this->dateTimeSmall = $lang['_dateTimeSmall'];
		unset($lang['_locale']);
		unset($lang['_charset']);
		unset($lang['_dateTimeFull']);
		unset($lang['_dateTimeMid']);
		unset($lang['_dateTimeSmall']);
		$this->labels = $lang;
	}

	protected function label($string, array $data=null) {
		$return = isset($this->labels[$string]) ? $this->labels[$string] : $string;
		if (is_array($data))
		foreach ($data as $key => $val)
		$return = str_replace("{{$key}}", $val, $return);
		return $return;
	}

	protected function backMsg($message, array $data=null) {
		$message = $this->label($message, $data);
		if (isset($this->file['tmp_name']) && file_exists($this->file['tmp_name']))
		@unlink($this->file['tmp_name']);
		$this->callBack("", $message);
		die;
	}

	protected function callBack($url, $message="") {
		$message = Application_Model_kclib_Text::jsValue($message);
		$CKfuncNum = isset($this->opener['CKEditor']['funcNum'])
		? $this->opener['CKEditor']['funcNum'] : 0;
		if (!$CKfuncNum) $CKfuncNum = 0;
		header("Content-Type: text/html; charset={$this->charset}");

		?>
<html>
<body>
	<script type='text/javascript'>
	var kc_CKEditor = (window.parent && window.parent.CKEDITOR)
	    ? window.parent.CKEDITOR.tools.callFunction
	    : ((window.opener && window.opener.CKEDITOR)
	        ? window.opener.CKEDITOR.tools.callFunction
	        : false);
	var kc_FCKeditor = (window.opener && window.opener.OnUploadCompleted)
	    ? window.opener.OnUploadCompleted
	    : ((window.parent && window.parent.OnUploadCompleted)
	        ? window.parent.OnUploadCompleted
	        : false);
	var kc_Custom = (window.parent && window.parent.KCFinder)
	    ? window.parent.KCFinder.callBack
	    : ((window.opener && window.opener.KCFinder)
	        ? window.opener.KCFinder.callBack
	        : false);
	if (kc_CKEditor)
	    kc_CKEditor(<?php echo $CKfuncNum ?>, '<?php echo $url ?>', '<?php echo $message ?>');
	if (kc_FCKeditor)
	    kc_FCKeditor(<?php echo strlen($message) ? 1 : 0 ?>, '<?php echo $url ?>', '', '<?php echo $message ?>');
	if (kc_Custom) {
	    if (<?php echo strlen($message) ?>) alert('<?php echo $message ?>');
	    kc_Custom('<?php echo $url ?>');
	}
	if (!kc_CKEditor && !kc_FCKeditor && !kc_Custom)
	    alert("<?php echo $message ?>");
	</script>
</body>
</html>



<?php

	}

	protected function get_htaccess() {
		return "<IfModule mod_php4.c>
	  php_value engine off
	</IfModule>
	<IfModule mod_php5.c>
	  php_value engine off
	</IfModule>
	";
	}

	public function getOpener(){
		return $this->opener;
	}

	public function getSession(){
		return $this->session;
	}

	public function getConfig(){
		return $this->config;
	}

	public function getType(){
		return $this->type;
	}

	public function getLang(){
		return $this->lang;
	}

	public function getGet(){
		return $this->get;
	}

	public function getCms(){
		return $this->cms;
	}

	public function getLabels(){
		return $this->labels;		
	}
	
	public function getVersion()
	{
		return self::VERSION;
	}

}
