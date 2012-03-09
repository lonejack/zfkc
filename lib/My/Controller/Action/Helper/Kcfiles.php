<?php

class My_Controller_Action_Helper_Kcfiles extends Zend_Controller_Action_Helper_Abstract
{
	const WIN 			= 0;
	const LINUX 		= 1;
	const THUMBS_DIR 	= '.thumbs';

	/** GD resource
	 * @var resource */
	protected $image;

	/** Image width
	 * @var integer */
	protected $width;

	/** Image height
	 * @var integer */
	protected $height;

	/** Init error
	 * @var bool */
	public $init_error = false;

	/** Last builded image type constant (IMAGETYPE_XXX)
	 * @var integer */
	public $type;


	protected $_zipfile;

	/**
	 *
	 * @var array, configure the helper
	 */
	protected $_config;

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

	/*************************************
	 * METHODS FOR THUMBS CREATION
	************************************/
	
	protected function imagecopyresampled($source,$destination)
	{
		$thumbWidth = $this->_config['thumbWidth'];
		$thumbHeight = $this->_config['thumbHeight'];
		$this->calcImageRate($info['width'], $info['height'], $thumbWidth, $thumbHeight);
		return imagecopyresampled($destination, $source, 
				$thumbWidth, $thumbHeight, 
				$src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

	}

	/** Resize image. Returns TRUE on success or FALSE on failure
	 * @param integer $width
	 * @param integer $height
	 * @return gd resource (the image resized)/ false */

	public function resize($gd, $width, $height) {
		if (!$width) $width = 1;
		if (!$height) $height = 1;
		
		$destination = &imagecreatetruecolor($width, $height);

		if( $destination === false )
			return false;
		return imagecopyresampled($destination, $gd);
	}
	
	/**
	 * calculate how to resize the source image 
	 * @param int $src_widht, source image width
	 * @param int $src_height, source image height
	 * @param int $dst_width, destination image width(by reference)
	 * @param int $dst_height, destination image height(by reference)
	 */
	public function calcImageRate($src_widht, $src_height, &$dst_width, &$dst_height) {
		if ((!$dst_width && !$dst_height) || (($dst_width == $src_widht) && ($dst_height == $src_height)))
			return true;
		if (!$dst_width || (($dst_height / $dst_width) < ($src_height / $src_widht)))
			$dst_width = intval(($src_widht * $dst_height) / $src_height);
		elseif (!$dst_height || (($dst_width / $dst_height) < ($src_widht / $src_height)))
		$dst_height = intval(($src_height * $dst_width) / $src_widht);
		if (!$dst_width) $dst_width = 1;
		if (!$dst_height) $dst_height = 1;
	}

	/** Returns an array. Element 0 - GD resource. Element 1 - width. Element 2 - height.
	 * Returns FALSE on failure. The only one parameter $image can be an instance of this class,
	 * a GD resource, an array(width, height) or path to image file.
	 * @param string(path) $image
	 * @return array */

	protected function _getResource($image, &$info) {
		$options = @getimagesize($image);
		if (false !== $options ) {
			list($width, $height, $type) = $options;
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
		}

		if(is_resource($image) && isset($width) && isset($height) &&	(preg_match('/^[1-9][0-9]*$/', $width) !== false) &&
				(preg_match('/^[1-9][0-9]*$/', $height) !== false))
		{
			$info['width'] =$width;
			$info['height'] =$height;
			$info['type'] =$type;
			return $image;
		}
		return false;
	}

	public function makeThumb($source, $destination, $overwrite=false){
		$gd = $this->_getResource($source, $info_gd = array());

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
		if (($info['width'] <= $this->_config['thumbWidth']) &&
				($info['height'] <= $this->_config['thumbHeight'])) {
			$browsable = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
			// Drop only browsable types
			if (in_array($info['type'], $browsable))
				return true;

			// Resize image
		}
		else{
			
			$gd = $this->resize($gd, $info['width'], $info['height']);
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

	/**
	 * get files info on directory
	 * @param path(string) $uploadDir, absolute path to download dir
	 * @param unknown_type $dir, sub directory
	 */

	function getFiles($uploadDir,$dir) {

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
			$ext = Application_Model_kclib_File::getExtension($file);
			$theme = $this->_config['theme'];
			$bigIcon = file_exists("themes/$theme/img/files/big/$ext.png");
			$smallIcon = file_exists("themes/$theme/img/files/small/$ext.png");
			$thumb = file_exists("$thumbDir/$name");
			$return[] = array(
					'name' => stripcslashes($name),
					'size' => $stat['size'],
					'mtime' => $stat['mtime'],
					'date' => @strftime($this->_config['format_date'], $stat['mtime']),
					'readable' => is_readable($file),
					'writable' => Application_Model_kclib_File::isWritable($file)?'true':'false'	,
					'bigIcon' => $bigIcon,
					'smallIcon' => $smallIcon,
					'thumb' => $thumb,
					'smallThumb' => $smallThumb
			);
		}
		return $return;
	}

	/**
	 *
	 * @param path(string) $upload_dir absolute path to upload dir
	 * @param path(string) $dir relative path requested
	 * @throws Exception
	 */
	function checkDir( $upload_dir, $dir ) {
		$directory = realpath($upload_dir.'/'. $dir);
		if( strncmp($upload_dir, $directory, strlen($upload_dir))  )
		{
			throw new Exception('Invalid request!', 3);
		}

		if ( !is_dir($directory) )
			throw new Exception('Inexistant folder.', 4);

		if( !is_readable($directory) )
			throw new Exception('Inaccessible folder.', 5);

		return $directory;
	}

	/**
	 * Checks if the given directory is really writable. The standard PHP
	 * function is_writable() does not work properly on Windows servers
	 * @param string $dir
	 * @return bool
	 */

	function isWritable($dir) {
		$dir = Application_Model_kclib_Path::normalize($dir);
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



		return $info;
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
		usort($files, 'My_Controller_Action_Helper_Kcfiles::fileNameCompare');
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

}
