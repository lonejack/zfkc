<?php

class My_Controller_Action_Helper_Kcfiles extends Zend_Controller_Action_Helper_Abstract
{
	const WIN 				= 0;
	const LINUX 			= 1;
	const THUMBS_DIR 		= '.thumbs';
	const CHAR_SET      	= 'utf-8';

	/**
	 *
	 * @var array, helper configuration
	 */
	protected $_config;

	/**
	 *
	 * @var array, contains the parameters requested
	 */
	protected $_params = array();


	/**
	 * Constructor
	 * @return void
	 */
	public function __construct()
	{


	}
	
	/**
	 * configure the helper
	 * @param Zend_Config $config
	 * @return this, the helper object
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
	 * Localization 
	 */
	
	/**
	 * get from request the language
	 * @return language code|string
	 */
	public function getRequestedLanguage(){
		$langInputNames = array('lang', 'langCode', 'lng', 'language', 'lang_code');
		$request = $this->getRequest();
		foreach ($langInputNames as $key) {
			$param = $request->getParam($key);
			if (isset($param) ) 
				return $param;
		}
		return 'en';
	}
	
	/**
	 * get the translator object that must be used on controller/view scripts
	 * @param  set the default language, if null get the defaultlanguage from configuration
	 * @return Zend_Translate object containing the default language and the requested language
	 */
	public function getTranslator($default = null){
		if( is_null( $default ) )
			$default = $this->_config['defaultlanguage'];
		$translator = new Zend_Translate( array(
				'adapter' => 'csv',
				'content' => APPLICATION_PATH."/language/$default/kc.csv",
				'locale'  => $default
		));
		$language = $this->getRequestedLanguage();
		if($language!='en')
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

	/**
	 * set the header and send an image to the client 
	 * @param $path to image
	 */
	public function sendImage($path){
		$ext = strtolower( $this->getExtension($path) );
		if( $ext != 'png' )
			$ext = 'jpeg';

		$this->setHeader('Content-Type',"image/$ext",true);
		readfile($path);
	}

	/**
	 * set headers and send a zip file to the client
	 * @param string $path to file
	 * @param string $filename, define the filename destination
	 * @param bool $unlink, if true unlink the file sent
	 */
	public function sendZip($path, $filename=null, $unlink=false){
		if( is_null($filename))
			$filename=basename($path);
		$this->setHeader('Content-Type','application/x-zip',true)->
		setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')->
		setHeader('Content-Length',filesize($path));
		readfile($path);
		if( $unlink )
			unlink($path);
	}

	/**
	 * set the headers to answer, if $header coresponds to 'Content-Type' set other info else 
	 * this method is identical to $response->setHeader($header, $option);
	 * @param string $header
	 * @param string $option argument
	 * @param bool $clearall, if true remove all prevoius headers
	 * @return My_Controller_Action_Helper_Kcfiles
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
	 * create a temporary randomic filename based on session that can be stored on a base directory
	 * the method check also if filename generated exists on the directory 
	 * @param string $base_dir
	 * @param string $extension
	 * @throws Exception
	 * @return string the filename
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
	 * zip a folder
	 * @param string $folder_source containing the path to source folder
	 * @param string $file_destination containing the path to destination file
	 * @param array $ignored contain the files that mustn't be stored 
	 * @throws Exception
	 */
	public function zipFolder($folder_source, $file_destination, $ignored = array() ){
		$zipfile = new ZipArchive();
		$this->_zipFolder($folder_source, $file_destination, $ignored, $zipfile );
	}
	
	/**
	 * @see My_Controller_Action_Helper_Kcfiles::zipFolder
	 * @param ZipArchive $zipfile
	 * @throws Exception
	 */
	protected function _zipFolder($folder_source, $file_destination, $ignored = array(), $zipfile ){
	
		if( !is_array($ignored) )
			throw new Exception("ignored must be an array");
			
		if ($zipfile->open($file_destination, ZIPARCHIVE::CREATE) !== TRUE)
			throw new Exception("cannot open <$file>\n");
		$folder_source = rtrim($folder_source, '/');

		if (strstr($folder_source, '/')) {
			$root = substr($folder_source, 0, strrpos($folder_source, '/') + 1);
			$folder = substr($folder_source, strrpos($folder_source, '/') + 1);
		}
		$this->_zip($root, $folder, $ignored, $zipfile);
		$zipfile->close();
	}

	/**
	 * recursive method that store files contained in a folder
	 * @param string $root
	 * @param string $folder
	 * @param array $ignored conating the files do not store in the archive 
	 * @param string $parent
	 * @param ZipArchive $zipArchive
	 */
	protected function _zip($root, $folder, $ignored, $parent=null, $zipArchive ) {
		$full_path = "$root$parent$folder";
		$zip_path = "$parent$folder";
		$zipArchive->addEmptyDir($zip_path);
		$dir = new DirectoryIterator($full_path);
		foreach ($dir as $file)
			if (!$file->isDot()) {
			$filename = $file->getFilename();
			if (!in_array($filename, $ignored)) {
				if ($file->isDir())
					$this->_zip($root, $filename, $ignored, "$zip_path/", $zipArchive);
				else
					$zipArchive->addFile("$full_path/$filename", "$zip_path/$filename");
			}
		}
	}

	/**
	 * build a zip archive and store inside a files list
	 * @param array $list of files
	 * @param string $file_destination of file destination
	 * @throws Exception if the function requested doesn't work
	 */
	public function zipList($list, $file_destination ){
		$zipfile = new ZipArchive();
			
		if ($zipfile->open($file_destination, ZIPARCHIVE::CREATE) !== TRUE)
			throw new Exception("cannot open <$file>\n");
		foreach ($list as $item ){
			$zipfile->addFile($item, basename($item));
		}
		$zipfile->close();
	}

	/**
	 * given an array of files check if there are hidden files on the list and remove them
	 * @param array $files of string
	 * @return boolean, true if at least one hidden file has been founded, false otherwise
	 */
	
	public function filterHidden(&$files){
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

	/**
	 * determine if a file/array of files is/are readable
	 * @param array/string $files
	 * @return boolean, true if readable
	 */
	public function checkReadable($files){
		$readable = true;
		if( !is_array($files))
			$files = (array)$files;
		foreach ($files as $key => $file){
			if(!is_readable($file))
				$readable = false;
		}
		return $readable;
	}

	/**
	 * prepend a string to an array of strings
	 * @param string $prefix
	 * @param array $list
	 */
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
	 * get a param from request and perform a check on some specific parameter 
	 * @param string $param, can be 'dir', 'type'...
	 * @return the parameter requested
	 * @throws exceptions if the requested parameter isn't allowed
	 */
	public function getParam($param, $default = null) {
		if( isset( $this->_params[$param] ) ) {
			return $this->_params[$param];
		}
		$parameter = $this->getRequest()->getParam($param, $default);
		
		switch($param){
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
					$this->_params[$param] = $parameter;
				else
					throw new Zend_Exception('Invalid request');
				break;

			default:
				$this->_params[$param] = $parameter;
				break;
		}
		return $this->_params[$param];
	}

	/*************************************
	 * Images methods
	************************************/

	/**
	 * build a gd resource resampling an input resource(source)
	 * @param gd resource $source
	 * @param int $src_widht
	 * @param int $src_height
	 * @return boolean|resource, false if the creation fail, a gd resource otherwise
	 */
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
	 * get info on an image, dimension
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

	/** 
	 * Returns an array. Element 0 - GD resource. Element 1 - width. Element 2 - height.
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
	
	/**
	 * get a default thumb based on the file extension 
	 * @param string $file
	 */
	protected function _getDefaultThumb( $file ){
		$ext = $this->_kcfiles->getExtension($file);
		$thumb =  PUBLIC_PATH.'/'.$this->_kcfiles->kcPath . "/{$this->_kcfiles->theme}/img/files/big/$ext.png";
		if( !is_file($thumb))
			return false;
		return $thumb;
	}
	
	/**
	 * make dir (like mkdir) bus set permission as defined in configuration
	 * @param string $directory
	 */
	public function mkdir($directory) {
		$perm = $this->_config['dirPerms'];
		if (!is_dir($directory) )
			@mkdir($directory,$perm , true);
		return is_dir($directory);
	}

	/**
	 * create the thumb
	 * @param string $dir
	 * @param string $file
	 * @param bool $overwrite, if true rebuild the thumb also if exist yet
	 */
	
	public function makeThumb($dir, $file, $overwrite=false){
		$source = $this->getUploadDir(array($dir,$file),false);
		$destination = $this->getThumbDir(array($dir,$file),false);
		if( !is_file($source))
			return false;
		
		if( is_file($destination) && !$overwrite )
			return $destination;
		
		$info_gd = $this->getInfoImage($source);
		if ($info_gd===false)
			return $this->_getDefaultThumb($file);

		$gd = $this->_getResource($source, $info_gd['type']);
		// Drop files which are not GD handled images
		if ($gd===false)
			return $this->_getDefaultThumb($file);

		$thumbDir = dirname($destination);
		$perm = $this->_config['dirPerms'];
		if (!is_dir($thumbDir) && !@mkdir($thumbDir,$perm , true))
			return $this->_getDefaultThumb($file);

		// Images with smaller resolutions than thumbnails
		if (($info_gd['width'] <= $this->_config['thumbWidth']) &&
				($info_gd['height'] <= $this->_config['thumbHeight'])) {
			$browsable = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
			// Drop only browsable types
			if (!in_array($info_gd['type'], $browsable))
			{
				// get default thumb based on extension
				return $this->_getDefaultThumb($file);
			}
		}
		else{

			$gd = $this->_getResourceDestination($gd, $info_gd['width'], $info_gd['height']);
			if ($gd == false)
				return $this->_getDefaultThumb($file);
		}
		// Save thumbnail
		imagejpeg($gd, $destination, $this->_config['jpegQuality']);
		chmod($destination, $this->_config['filePerms']);
		return $destination;
	}
	
	/***********************************
	 * FILES METHODS
	***********************************/
	/**
	 * Create a translation file 
	 * @param array $array, of strings
	 * @param string $destination path
	 * @param string $format, allowed types: csv
	 * @return true on success 
	 */
	public function buildTranslation($array, $destination, $format='csv') {
		if($format != 'csv')
			return false;
		$file = fopen($destination, 'w');
		foreach ($array as $key => $value ){
			fprintf($file, "\"%s\";\"%s\"\n",$key,$value);
			
		}
		fclose($file);
		chmod($destination, $this->_config['filePerms']);
	}
	/**
	 * get thumbs directory and append to it further subdirectory
	 * @param string|array of string $subdir
	 * @param bool $end_directory_separator, define the end symbol at the end of the path
	 * @return string path requested
	 */
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

	/**
	 * get upload directory and append to it further subdirectory
	 * @param string|array of string $subdir
	 * @param bool $end_directory_separator, define the end symbol at the end of the path
	 * @return string path
	 */
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

	/**
	 * prepend a path to an entity(by reference) and if requested check its existence 
	 * @param string $path
	 * @param string|array of string $subject
	 * @param bool $checkExistence
	 * @return boolean, true if checkexistence parameter isn't set to true
	 */
	function prependPath($path, &$subject, $checkExistence = true ){
		$path =rtrim($path,'/');
		
		if(is_array($subject)) {
			foreach ($subject as $key => $item)
			{	
				$subject[$key] = $path.DIRECTORY_SEPARATOR.ltrim($item,'/');
				if( $checkExistence && !file_exists($subject[$key]) )
					return false;
			}
		}
		else {
			$subject = ltrim($subject,'/');
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

	/**
	 * check existence/readblilty of a file/array of files
	 * @param string/arry of strings $names
	 * @return bool, true/false
	 */
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

	function getFiles($uploadDir,$subdir) {
		$uploadDir = rtrim($uploadDir,'/');
		$subdir = ltrim($subdir,'/');
		
		$thumbDir = "$uploadDir/".self::THUMBS_DIR."/$subdir";
		$dir = "$uploadDir/$subdir";
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
					$this->makeThumb($subdir, basename($file));
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
	 *******************************/
	/**
	 * get path to js code 
	 * @param string $append
	 * @param string $ds, DIRECTORY_SEPARATOR
	 * @return the complete path
	 */
	public function getRealPath($append = null, $ds = DIRECTORY_SEPARATOR) {
		$path = PUBLIC_PATH.$ds.$this->_config['kcPath'];
		if( !is_null($append) ) {
			$dir = ltrim($append,$ds);
			$path .= $ds . $dir;
		}
		return $path;		
	}
	
	/**
	 * get path to translation dir
	 * @param string $append
	 * @param string $ds, DIRECTORY_SEPARATOR
	 * @return the complete path
	 */
	public function getTranslationDir($append = null, $ds = DIRECTORY_SEPARATOR) {
		$path = $this->_config['translationDir'];
		if( !is_null($append) ) {
			$dir = ltrim($append,$ds);
			$path .= $ds . $dir;
		}
		return $path;
	}
	
	/**
	 * get directory size under a directory
	 * @param string $path
	 * @return integer size in bytes
	 */
	public function getDirSize($path)
	{
		$io = popen('/usr/bin/du -sb '.$path, 'r');
		$size = intval(fgets($io,80));
		pclose($io);
		return $size;
	}

	/**
	 * get session dir, if the parameter isn't set it will be created(taking the default parameter on configuration)
	 * @return string, the directory stored
	 */
	public function getSessionDir(){

		$zf_kceditor = new Zend_Session_Namespace('zf_kceditor');
		if( !isset($zf_kceditor->sessionDir) ) {
			$sessionDir = $this->getRequest()->getParam('type',trim($this->_kcfiles['imagesDir'],'/'));
			$zf_kceditor->sessionDir = $sessionDir;
		}
		return $zf_kceditor->sessionDir;
	}

	/**
	 * Store the session directory 
	 * @param string, the directory to store
	 */
	public function setSessionDir($dir){

		$zf_kceditor = new Zend_Session_Namespace('zf_kceditor');
		$zf_kceditor->sessionDir = $dir;
	}

	/**
	 * Check if the requested dir is: pointing to an allowed dir, a directory, readable 
	 * @param path(string) $dir absolute path 
	 * @return bool, true if the three conditions are valid 
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

	/** 
	 * Normalize the given path. On Windows servers backslash will be replaced
	 * with slash. Remobes unnecessary doble slashes and double dots. Removes
	 * last slash if it exists. Examples:
	 * Application_Model_kclib_Path::normalize("C:\\any\\path\\") returns "C:/any/path"
	 * Application_Model_kclib_Path::normalize("/your/path/..//home/") returns "/your/home"
	 * @param string $path
	 * @return string 
	 **/

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
	 * get directory info
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
			$type = @filetype("$dir/$file");

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

	/**
	 * Get a directory three starting from a base directory, this method is internal and has been built
	 * for recursive calls
	 * @param string $baseDir
	 * @param string $dpath, the path that must be followed for analisys
	 * @param integer $index of the token($dpath) considered
	 * @return array
	 */
	protected function _getTree($baseDir, $dpath, $index=0) {

		static $sub_dir;
		$paths = array();
		if( $index == 0 )
		{
			//build the tree on $path
			
			$paths = $this->getDirInfo($baseDir);
			if( !empty($dpath))
				$sub_dir = explode("/", $dpath);
			
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

	/**
	 * do the init act
	 * @param string $type, see types parameter on configuration
	 * @param string $sessionDir
	 * @return array
	 */
	function init_browser($type, $sessionDir) {

		//$tree = self::getDirInfo($uploadDir);

		$tree = $this->_getTree($this->getUploadDir($type,false), $sessionDir);
		if (!is_array($tree['dirs']) || !count($tree['dirs']))
			unset($tree['dirs']);
		$files = $this->getFiles($this->getUploadDir(),$type.'/'.$sessionDir);
		$dirWritable = $this->isWritable($this->getUploadDir()."$type/$sessionDir");
		$data = array(
				'tree' => &$tree,
				'files' => &$files,
				'dirWritable' => $dirWritable
		);
		return $data;
	}

	/**
	 * remove the first token from a given path
	 * @param string $path
	 * @return string
	 */
	function removeTypeFromPath($path) {
		return preg_match('/^[^\/]*\/(.*)$/', $path, $patt)
		? $patt[1] : "";
	}

	/**
	 * magic method for access to configuration parameters
	 * @param string $name
	 * @return the parameter stored in confuguration
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->_config)) {
			return $this->_config[$name];
		}
		return null;
	}
}
