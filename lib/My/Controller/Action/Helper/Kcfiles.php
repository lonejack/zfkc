<?php

class My_Controller_Action_Helper_Kcfiles extends Zend_Controller_Action_Helper_Abstract
{

	protected $_zipfile;

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
	 * @param path $base_dir
	 */
	public function getTemporaryFileName($base_dir, $extension)
	{
		if( !Zend_Session::isStarted() )
			throw new Exception("Session not allowable");
			
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


}
