<?php

class My_Controller_Action_Helper_Kcfiles extends Zend_Controller_Action_Helper_Abstract
{
	const WIN 				= 0;
	const LINUX 			= 1;
	const THUMBS_DIR 		= '.thumbs';
	const CHAR_SET      	= 'utf-8';

	protected $_zipfile;

	/**
	 *
	 * @var array, configure the helper
	 */
	protected $_config;

	/**
	 *
	 * @var array, contains the parameters requested
	 */
	protected $_params = array();


	/**
	 * Constructor
	 *
	 * Register action stack plugin
	 *
	 * @return void
	 */
	public function __construct()
	{


	}
	
	/**
	 * Localization 
	 */
	
	protected function _getRequestedLanguage(){
		$langInputNames = array('lang', 'langCode', 'lng', 'language', 'lang_code');
		$request = $this->getRequest();
		foreach ($langInputNames as $key) {
			$param = $request->getParam($key);
			if (isset($param) ) 
				return $param;
		}
		return null;
	}
	
	public function getTranslator($default = 'en'){
		$translator = new Zend_Translate( array(
				'adapter' => 'csv',
				'content' => APPLICATION_PATH."/language/$default/kc.csv",
				'locale'  => $default
		));
		$language = $this->_getRequestedLanguage();
		if(!is_null($language))
		{
			$translator->addTranslation(
					array(
							'content' => APPLICATION_PATH."/language/$language/kc.csv",
							'locale'  => $language,
							'route'   => array($language => $default)
					)
			);
			$translator->setLocale($language);
		}
		else 
			$translator->setLocale($default);
		return $translator;
	}

	public function sendImage($image){
		$ext = strtolower( $this->getExtension($image) );
		if( $ext != 'png' )
			$ext = 'jpeg';

		$this->setHeader('Content-Type',"image/$ext",true);
		readfile($image);
	}

	public function sendZip($file, $filename=null, $unlink=false){
		if( is_null($filename))
			$filename=basename($file);
		$this->setHeader('Content-Type','application/x-zip',true)->
		setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')->
		setHeader('Content-Length',filesize($file));
		readfile($file);
		if( $unlink )
			unlink($file);
	}

	/**
	 *
	 * @param unknown_type $type
	 */
	public function setHeader($header, $option = NULL, $clearall = false){
		$response = $this->getResponse();
		if($clearall)
			$response->clearAllHeaders();

		switch($header){
			default:
				$response->setHeader($header, $option);
				break;

			case 'Content-Type':
				switch( $option ) {
					case 'application/json':
					case 'text/plain':
					case 'text/javascript':
						$response->setHeader('Content-Type', "$option; charset=".self::CHAR_SET);
						break;
					default:
						$response->setHeader('Content-Type', $option);
						break;
				}
				break;
					
		}
		return $this;
	}

	/**
	 *
	 * @param unknown_type $options
	 */
	public function Config($config){

		if( $config instanceof Zend_Config ) {
			$this->_config = $config->toArray();
			$this->_config['dirPerms'] = octdec($this->_config['dirPerms']);
			$this->_config['filePerms'] = octdec($this->_config['filePerms']);
		}
		return $this;
	}

	/**
	 *
	 * @param path $base_dir
	 */
	public function getTemporaryFileName($base_dir, $extension='txt')
	{
		if( !Zend_Session::isStarted() )
			throw new Exception("Session not availabe");
			
		$id = Zend_Session::getId();
		do {
			$file = md5(time() . $id);
			$file = $base_dir.DIRECTORY_SEPARATOR.$file.".$extension";
		} while (file_exists($file));
		return $file;
	}
	/**
	 *
	 * @param path to $folder_source
	 * @param path to $file_destination
	 */
	public function zipFolder($folder_source, $file_destination, $ignored = array() ){
		$this->_zipfile = new ZipArchive();
			
		if( !is_array($ignored) )
			throw new Exception("ignored must be an array");
			
		if ($this->_zipfile->open($file_destination, ZIPARCHIVE::CREATE) !== TRUE)
			throw new Exception("cannot open <$file>\n");
		$folder_source = rtrim($folder_source, '/');

		if (strstr($folder_source, '/')) {
			$root = substr($folder_source, 0, strrpos($folder_source, '/') + 1);
			$folder = substr($folder_source, strrpos($folder_source, '/') + 1);
		}
		$this->_zip($root, $folder, $ignored);
		$this->_zipfile->close();
	}

	protected function _zip($root, $folder, $ignored, $parent=null) {
		$full_path = "$root$parent$folder";
		$zip_path = "$parent$folder";
		$this->_zipfile->addEmptyDir($zip_path);
		$dir = new DirectoryIterator($full_path);
		foreach ($dir as $file)
			if (!$file->isDot()) {
			$filename = $file->getFilename();
			if (!in_array($filename, $ignored)) {
				if ($file->isDir())
					$this->_zip($root, $filename, $ignored, "$zip_path/");
				else
					$this->_zipfile->addFile("$full_path/$filename", "$zip_path/$filename");
			}
		}
	}

	public function zipList($list, $file_destination ){
		$zipfile = new ZipArchive();
			
		if ($zipfile->open($file_destination, ZIPARCHIVE::CREATE) !== TRUE)
			throw new Exception("cannot open <$file>\n");
		foreach ($list as $item ){
			$zipfile->addFile($item, basename($item));
		}
		$zipfile->close();
	}

	public function filterHidden($files){
		$found = false;
		foreach ($files as $key => $file){
			$tokens = explode('/', $file);
			foreach ( $tokens as $token ) {
				if( substr( $token, 0,1 )=='.') {
					unset( $files[$key] );
					$found = true;
				}
			}
		}
		return $found;
	}

	public function checkReadable($files){
		$readable = true;
		foreach ($files as $key => $file){
			if(!is_readable($file))
				$readable = false;
		}
		return $readable;
	}

	public function prepend($prefix,$list){
		$result = array();
		foreach ($list as $key => $item ){
			if(is_string($item))
				$result[] = $prefix.$item;
		}
		return $result;
	}

	/**
	 * methods for input checking
	 */

	
	/**
	 *
	 * @param string $param, can be 'dir', 'type'...
	 */
	public function getParam($subject, $default = null) {
		if( isset( $this->_params[$subject] ) ) {
			return $this->_params[$subject];
		}
		$parameter = $this->getRequest()->getParam($subject, $default);
		
		switch($subject){
			case 'type':
				if( in_array($parameter, array('images','flash','files')) )
					$this->_params['type'] = $parameter;
				else
					throw new Zend_Exception('Invalid request');
				break;

			case 'dir':
				$type = $this->getParam('type');
				// check that the first piece of 'dir' corresponds to type
				$pieces = explode('/', $parameter);
					
				if( $pieces[0] == $type )
					$this->_params[$subject] = $parameter;
				else
					throw new Zend_Exception('Invalid request');
				break;

			default:
				$this->_params[$subject] = $parameter;
				break;
		}
		return $this->_params[$subject];
	}

	/*************************************
	 * METHODS FOR THUMBS CREATION
	************************************/

	protected function _getResourceDestination($source, $src_widht, $src_height)
	{
		$dst_width = $this->_config['thumbWidth'];
		$dst_height = $this->_config['thumbHeight'];
		//$destination =$this->_createResDestination($info['width'], $info['height'], $thumbWidth, $thumbHeight);
		if ((!$dst_width && !$dst_height) || (($dst_width == $src_widht) && ($dst_height == $src_height)))
			return true;

		if (!$dst_width || (($dst_height / $dst_width) < ($src_height / $src_widht))) {
			$dst_width = intval(($src_widht * $dst_height) / $src_height);
		}
		elseif (!$dst_height || (($dst_width / $dst_height) < ($src_widht / $src_height))) {
			$dst_height = intval(($src_height * $dst_width) / $src_widht);
		}
		if (!$dst_width)
			$dst_width = 1;
		if (!$dst_height)
			$dst_height = 1;
		$destination = &imagecreatetruecolor($dst_width, $dst_height);

		if( $destination === false )
			return false;
		$result = @imagecopyresampled($destination, $source, 0, 0, 0, 0,
				$dst_width, $dst_height, $src_widht, $src_height);
		if( !$result )
			return false;
		return $destination;

	}

	/**
	 *
	 * @param string $source path to file
	 * @return array indicating info regarding the image/false if the procedure fail
	 */
	public function getInfoImage($image){
		$options = @getimagesize($image);
		if (false === $options &&
				preg_match('/^[1-9][0-9]*$/', $options[0]) !== false &&
				preg_match('/^[1-9][0-9]*$/', $options[1]) !== false ){
			return false;
		}
		$options['width'] 	= $options[0];
		$options['height']	= $options[1];
		$options['type'] 	= $options[2];
		$options['sizestr'] = $options[3];
		unset($options[0]);
		unset($options[1]);
		unset($options[2]);
		unset($options[3]);

		return $options ;

	}

	/** Returns an array. Element 0 - GD resource. Element 1 - width. Element 2 - height.
	 * Returns FALSE on failure. The only one parameter $image can be an instance of this class,
	 * a GD resource, an array(width, height) or path to image file.
	 * @param string(path) $image
	 * @return array */

	protected function _getResource($image, $type) {

		switch($type){
			case IMAGETYPE_GIF:
				$image = @imagecreatefromgif($image);
				break;

			case IMAGETYPE_WBMP:
				$image = @imagecreatefromwbmp($image);
				break;

			case IMAGETYPE_JPEG:
				$image = @imagecreatefromjpeg($image);
				break;

			case IMAGETYPE_JPEG2000:
				$image = @imagecreatefromjpeg($image);
				break;

			case IMAGETYPE_PNG:
				$image = imagecreatefrompng($image);
				imagealphablending($image, false);
				break;

			case IMAGETYPE_XBM:
				$image = @imagecreatefromxbm($image);
				break;

			default:
				$image = false;
				break;

		}

		if(is_resource($image))
		{
			return $image;
		}
		return false;
	}

	public function makeThumb($source, $destination, $overwrite=false){
		$info_gd = $this->getInfoImage($source);
		if ($info_gd===false)
			return true;

		$gd = $this->_getResource($source, $info_gd['type']);
		// Drop files which are not GD handled images
		if ($gd===false)
			return true;

		$thumbDir = dirname($destination);
		$perm = $this->_config['dirPerms'];
		if (!is_dir($thumbDir) && !@mkdir($thumbDir,$perm , true))
			return false;

		if (!$overwrite && is_file($destination))
			return true; // file already present

		// Images with smaller resolutions than thumbnails
		if (($info_gd['width'] <= $this->_config['thumbWidth']) &&
				($info_gd['height'] <= $this->_config['thumbHeight'])) {
			$browsable = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
			// Drop only browsable types
			if (!in_array($info_gd['type'], $browsable))
				return true;

			// Resize image
		}
		else{

			$gd = $this->_getResourceDestination($gd, $info_gd['width'], $info_gd['height']);
			if ($gd == false)
				return false;
		}
		// Save thumbnail
		imagejpeg($gd, $destination, $this->_config['jpegQuality']);
		chmod($destination, $this->_config['filePerms']);
		return ;
	}


	/***********************************
	 * FILES METHODS
	***********************************/
	function getThumbDir($subdir = null, $end_directory_separator = true){
		$path =  $this->_config['uploadDir']. DIRECTORY_SEPARATOR. self::THUMBS_DIR;
		if(!is_array($subdir))
			$subdir = array($subdir);
		if( isset($subdir) ) {
			$path = rtrim($path,DIRECTORY_SEPARATOR);
			foreach ($subdir as $item )
				$path .= DIRECTORY_SEPARATOR.$item;
		}

		if($end_directory_separator)
			$path .= DIRECTORY_SEPARATOR;
		return $path;
	}

	function getUploadDir($subdir = null, $end_directory_separator = true){
		$path = $this->_config['uploadDir'];
		if(!is_array($subdir))
			$subdir = array($subdir);
		if( isset($subdir) ) {
			$path = rtrim($path,DIRECTORY_SEPARATOR);
			foreach ($subdir as $item )
				$path .= DIRECTORY_SEPARATOR.$item;
		}
		if($end_directory_separator && substr($path, -1) !== DIRECTORY_SEPARATOR) {
			$path .= DIRECTORY_SEPARATOR;
		}
		return $path;
	}

	function prependPath($path, &$subject, $checkExistence = true ){
		$path =rtrim($path,'/');
		$subject = ltrim($subject,'/');
		if(is_array($subject)) {
			foreach ($subject as $key => $item)
			{
				$subject[$key] = $path.DIRECTORY_SEPARATOR.$item;
				if( $checkExistence && !file_exists($subject[$key]) )
					return false;
			}
		}
		else {
			$subject = $path.DIRECTORY_SEPARATOR.$subject;
			if( $checkExistence && !file_exists($subject) )
				return false;
		}
		return true;
	}

	/** Get the extension from filename
	 * @param string $file
	 * @param bool $toLower
	 * @return string */

	function getExtension($filename, $toLower=true) {
		return preg_match('/^.*\.([^\.]*)$/s', $filename, $patt)
		? ($toLower ? strtolower($patt[1]) : $patt[1]) : "";
	}

	function existFile( $names ) {
		if(is_string($names)) {
			$names = array($names);
			$retString = true;
		}
		$list = array();
		foreach($names as $name) {
			$name = rtrim($name,DIRECTORY_SEPARATOR);
			$file = realpath($name);
			$list[]=$file;

			if( $file != $name  )
			{
				return false;//throw new Exception('Invalid request!',-1);
			}

			if ( !is_file($file) )
			{
				return false;//throw new Exception("file $name is Inexistant!",1);
			}
			if( !is_readable($file) )
			{
				return false;//throw new Exception("file $name is unreadable",2);
			}

		}
		if( isset($retString) ) {
			$list = $list[0];
		}
		return $list;
	}

	/**
	 * get files info on directory
	 * @param path(string) $uploadDir, absolute path to download dir
	 * @param unknown_type $dir, sub directory
	 */

	function getFiles($uploadDir,$dir) {
		$uploadDir = rtrim($uploadDir,'/');
		$dir = ltrim($dir,'/');
		
		$thumbDir = "$uploadDir/".self::THUMBS_DIR."/$dir";
		$dir = "$uploadDir/$dir";
		$return = array();
		$files = $this->getDirContent($dir, array('types' => "file"));
		if ($files === false){
			return $return;
		}

		foreach ($files as $file) {
			$size = @getimagesize($file);
			if (is_array($size) && count($size)) {
				$thumb_file = "$thumbDir/" . basename($file);
				if (!is_file($thumb_file))
					$this->makeThumb($file, $thumb_file);
				$smallThumb =
				($size[0] <= $this->_config['thumbWidth']) &&
				($size[1] <= $this->_config['thumbHeight']) &&
				in_array($size[2], array(IMAGETYPE_GIF, IMAGETYPE_PNG, IMAGETYPE_JPEG));
			} else
				$smallThumb = false;

			$stat = stat($file);
			if ($stat === false) continue;
			$name = basename($file);
			$ext = $this->getExtension($file);
			$theme = $this->_config['theme'];
			$bigPath = $this->_config['kcPath']."themes/$theme/img/files/big/$ext.png";
			$bigIcon = $this->prependPath(PUBLIC_PATH,$bigPath);
			$smallPath = $this->_config['kcPath']."themes/$theme/img/files/small/$ext.png";
			$smallIcon = $this->prependPath(PUBLIC_PATH,$smallPath);
			$thumbPath = "$thumbDir/$name";
			$thumb = file_exists($thumbPath);
			$return[] = array(
					'name' => stripcslashes($name),
					'size' => $stat['size'],
					'mtime' => $stat['mtime'],
					'date' => @strftime($this->_config['format_date'], $stat['mtime']),
					'readable' => is_readable($file),
					'writable' => $this->isWritable($file)?'true':'false'	,
					'bigIcon' => $bigIcon,
					'smallIcon' => $smallIcon,
					'thumb' => $thumb,
					'smallThumb' => $smallThumb
			);
		}
		return $return;
	}

	/*******************************
	 * Folder methods
	*/

	public function getSessionDir(){

		$zf_kceditor = new Zend_Session_Namespace('zf_kceditor');
		if( !isset($zf_kceditor->sessionDir) ) {
			$sessionDir = trim($this->_kcfiles['imagesDir'],'/');
			$zf_kceditor->sessionDir = $sessionDir;
		}
		return $zf_kceditor->sessionDir;
	}

	public function setSessionDir($dir){

		$zf_kceditor = new Zend_Session_Namespace('zf_kceditor');
		$zf_kceditor->sessionDir = $dir;
	}

	/**
	 *
	 * @param path(string) $upload_dir absolute path to upload dir
	 * @param path(string) $dir relative path requested
	 * @throws Exception
	 */
	function checkDir( $dir ) {
		$dir = rtrim($dir,DIRECTORY_SEPARATOR);
		$directory = realpath($dir);

		if( $directory != $dir  )
		{
			return false; //throw new Exception('Invalid request!', 3);
		}

		if ( !is_dir($directory) )
			return false; //throw new Exception('Inexistant folder.', 4);

		if( !is_readable($directory) )
			return false; //throw new Exception('Inaccessible folder.', 5);

		return true;
	}

	/** Normalize the given path. On Windows servers backslash will be replaced
	 * with slash. Remobes unnecessary doble slashes and double dots. Removes
	 * last slash if it exists. Examples:
	 * Application_Model_kclib_Path::normalize("C:\\any\\path\\") returns "C:/any/path"
	 * Application_Model_kclib_Path::normalize("/your/path/..//home/") returns "/your/home"
	 * @param string $path
	 * @return string */

	function normalize($path) {
		if ($this->getOs() == self::WIN) {
			$path = preg_replace('/([^\\\])\\\([^\\\])/', "$1/$2", $path);
			if (substr($path, -1) == "\\") $path = substr($path, 0, -1);
			if (substr($path, 0, 1) == "\\") $path = "/" . substr($path, 1);
		}

		$path = preg_replace('/\/+/s', "/", $path);

		$path = "/$path";
		if (substr($path, -1) != "/")
			$path .= "/";

		$expr = '/\/([^\/]{1}|[^\.\/]{2}|[^\/]{3,})\/\.\.\//s';
		while (preg_match($expr, $path))
			$path = preg_replace($expr, "/", $path);

		$path = substr($path, 0, -1);
		$path = substr($path, 1);
		return $path;
	}

	/**
	 * Checks if the given directory is really writable. The standard PHP
	 * function is_writable() does not work properly on Windows servers
	 * @param string $dir
	 * @return bool
	 */

	function isWritable($dir) {
		$dir =$this->normalize($dir);
		if (!is_dir($dir))
			return false;
		if ($this->getOs() == self::LINUX){
			return is_writable($dir);
		}
		// on windows...
		$file = $this->getTemporaryFileName($dir);
		if (!@touch($file))
			return false;
		unlink($file);
		return true;
	}

	/**
	 * get directory structure(including info)
	 * @param directory path $dir
	 */

	function getDirs($dir) {
		$dirs = $this->getDirContent($dir, array('types' => "dir"));
		$return = array();
		if (is_array($dirs)) {
			$writable = $this->isWritable($dir);
			foreach ($dirs as $cdir) {
				$info = $this->getDirInfo($cdir);
				if ($info === false) continue;
				$info['removable'] = $writable && $info['writable'];
				$return[] = $info;
			}
			return $return;
		}
		return null;
	}

	/**
	 * ged directory info
	 * @param path $dir
	 */

	function getDirInfo($dir) {
		if ((substr(basename($dir), 0, 1) == ".") || !is_dir($dir) || !is_readable($dir))
			return false;
		$dirs = $this->getDirContent($dir, array('types' => "dir"));
		if (is_array($dirs)) {
			foreach ($dirs as $key => $cdir)
				if (substr(basename($cdir), 0, 1) == ".")
				unset($dirs[$key]);
			$hasDirs = count($dirs) ? true : false;
		} else
			$hasDirs = false;

		$writable = $this->isWritable($dir);
		$info = array(
				'name' => stripslashes(basename($dir)),
				'readable' => is_readable($dir),
				'writable' => $writable,
				'removable' => $writable && $this->isWritable(dirname($dir)),
				'hasDirs' => $hasDirs
		);

		$check_dir = "{$this->_config['uploadDir']}/{$this->getSessionDir()}";
		if ($dir == $check_dir)
			$info['current'] = true;

		return $info;
	}

	/** Recursively delete the given directory. Returns TRUE on success.
	 * If $firstFailExit parameter is true (default), the method returns the
	 * path to the first failed file or directory which cannot be deleted.
	 * If $firstFailExit is false, the method returns an array with failed
	 * files and directories which cannot be deleted. The third parameter
	 * $failed is used for internal use only.
	 * @param string $dir
	 * @param bool $firstFailExit
	 * @param array $failed
	 * @return mixed */

	function prune($dir, $firstFailExit=true, array $failed=null) {
		if ($failed === null) $failed = array();
		$files = $this->getDirContent($dir);
		if ($files === false) {
			if ($firstFailExit)
				return $dir;
			$failed[] = $dir;
			return $failed;
		}

		foreach ($files as $file) {
			if (is_dir($file)) {
				$failed_in = $this->prune($file, $firstFailExit, $failed);
				if ($failed_in !== true) {
					if ($firstFailExit)
						return $failed_in;
					if (is_array($failed_in))
						$failed = array_merge($failed, $failed_in);
					else
						$failed[] = $failed_in;
				}
			} elseif (!@unlink($file)) {
				if ($firstFailExit)
					return $file;
				$failed[] = $file;
			}
		}

		if (!@rmdir($dir)) {
			if ($firstFailExit)
				return $dir;
			$failed[] = $dir;
		}
		$ne = count($failed);

		return ($ne == 0) ? true : $ne;
	}

	/** Get the content of the given directory. Returns an array with filenames
	 * or FALSE on failure
	 * @param string $dir
	 * @param array $options
	 * @return mixed
	 */
	function getDirContent($dir, array $options=null) {

		$defaultOptions = array(
				'types' => "all",   // Allowed: "all" or possible return values
				// of filetype(), or an array with them
				'addPath' => true,  // Whether to add directory path to filenames
				'pattern' => '/./', // Regular expression pattern for filename
				'followLinks' => true
		);

		if (!is_dir($dir) || !is_readable($dir))
			return false;

		if ($this->getOs() == self::WIN)
			$dir = str_replace("\\", "/", $dir);
		$dir = rtrim($dir, "/");

		$dh = @opendir($dir);
		if ($dh === false)
			return false;

		if ($options === null)
			$options = $defaultOptions;

		foreach ($defaultOptions as $key => $val)
			if (!isset($options[$key]))
			$options[$key] = $val;

		$files = array();
		while (($file = @readdir($dh)) !== false) {
			$type = filetype("$dir/$file");

			if ($options['followLinks'] && ($type === "link")) {
				$lfile = "$dir/$file";
				do {
					$ldir = dirname($lfile);
					$lfile = @readlink($lfile);
					if (substr($lfile, 0, 1) != "/")
						$lfile = "$ldir/$lfile";
					$type = filetype($lfile);
				} while ($type == "link");
			}

			if ((($type === "dir") && (($file == ".") || ($file == ".."))) ||
					!preg_match($options['pattern'], $file)) {
				continue;
			}


			if (($options['types'] === "all") || ($type === $options['types']) ||
					((is_array($options['types'])) && in_array($type, $options['types']))){
				$files[] = $options['addPath'] ? "$dir/$file" : $file;
			}

		}
		closedir($dh);

		usort($files, get_class($this).'::fileNameCompare');
		return $files;
	}

	/**
	 * function for name compare with lowercase
	 * @param string $a
	 * @param string $b
	 * @return 0 if equal, -1 if $a < $b, +1 otherwise
	 */

	static function fileNameCompare($a, $b) {
		if (function_exists("mb_strtolower")) {
			$a = mb_strtolower($a);
			$b = mb_strtolower($b);
		} else {
			$a = strtolower($a);
			$b = strtolower($b);
		}
		if ($a == $b) return 0;
		return ($a < $b) ? -1 : 1;
	}

	public function getOs(){
		if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN")
			return self::WIN;
		return self::LINUX;
	}

	protected function _getTree($baseDir, $dpath, $index=0) {

		static $sub_dir;
		$paths = array();
		if( $index == 0 )
		{
			//build the tree on $path
			$sub_dir = explode("/", $dpath);
			$paths = $this->getDirInfo($baseDir);
		}

		/* search for subdirs under basedir */
		$sub_paths = $this->getDirs($baseDir);
		if( is_array($sub_paths) )
		{

			foreach ($sub_paths as $key => $nPage)
			{
				if( isset($sub_dir[$index]) && $nPage['name'] == $sub_dir[$index] )
				{
					$sub_paths[$key]['dirs']= $this->_getTree($baseDir.'/'.$sub_dir[$index], null, $index+1);
				}
			}
			if( $index == 0 )
				$paths['dirs'] = $sub_paths;
			else
				$paths = $sub_paths;
		}
		return $paths;
	}

	function init_browser($uploadDir, $sessionDir) {

		//$tree = self::getDirInfo($uploadDir);

		$tree = $this->_getTree($uploadDir, $sessionDir);
		if (!is_array($tree['dirs']) || !count($tree['dirs']))
			unset($tree['dirs']);
		$files = $this->getFiles($uploadDir,$sessionDir);
		$dirWritable = $this->isWritable("$uploadDir/$sessionDir");
		$data = array(
				'tree' => &$tree,
				'files' => &$files,
				'dirWritable' => $dirWritable
		);
		return $data;
	}

	function removeTypeFromPath($path) {
		return preg_match('/^[^\/]*\/(.*)$/', $path, $patt)
		? $patt[1] : "";
	}

	/*
	 function getParam($param, $default = null ){
	$list = explode('/', $param);
	$start = $this->_config;
	foreach ($list as $token ) {
	if( !isset($start[$token]))
		return $default;
	$start = $start[$token];
	}
	return $start;
	}
	*/
	public function __get($name)
	{
		if (array_key_exists($name, $this->_config)) {
			return $this->_config[$name];
		}
		return null;
	}
}
