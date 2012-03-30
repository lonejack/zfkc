<?php

class KcController extends Zend_Controller_Action
{
	const DIRECTORY_LANGUAGES	= 'kcLanguages';
	const DIR_LANGUAGES = '/language';
	protected $_kcfinderDir;
	protected $_realpath;
	protected $_uploadDir;
	protected $_type;
	/**
	 *
	 * @var My_Controller_Action_Helper_Kcfiles
	 */
	protected $_kcfiles;

	public function init()
	{
		/* Initialize action controller here */
		$config = new Zend_Config_Ini(APPLICATION_PATH."/configs/KcConfig.ini", 'browser' );
		$this->_kcfiles = $this->_helper-> getHelper('Kcfiles')->Config($config);

		$this->_realpath = $this->_kcfiles->normalize(PUBLIC_PATH.'/'.$this->_kcfiles->kcPath);

		// define types we're working
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$request = $this->getRequest();
		$type = $request->getParam('type');
		if( in_array($type, array('images','flash','files')) )
			$this->_type = $type;
	}

	public function indexAction()
	{
		// action body
	}

	public function thumbAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		if(empty($dir) ) $dir = $this->_type;
		$file = $request->getParam('file',null);

		$file_real = $this->_kcfiles->getUploadDir(array($dir,$file),false);
		$thumb_real = $this->_kcfiles->getThumbDir(array($dir,$file),false);
		if( !isset($dir) && !isset($file) && !$this->_kcfiles->existFile($file_real) )
		{
			return $this->sendRaw( 'Unknown error.');
		}

		if( !$this->_kcfiles->existFile($thumb_real) )
			$this->_kcfiles->makeThumb($file_real, $thumb_real);

		if( !$this->_kcfiles->existFile($thumb_real) ) {
			/**
			 * TODO: if thumb doesn't exist log the message
			 */
			$default = true;// get the default
		}

		if( isset($default ) )
		{
			$ext = $this->_kcfiles->getExtension($file_name);
			$thumb = "{$this->_realpath}/themes/{$this->_kcfiles->theme}/img/files/big/$ext.png";
		}
		else
		{
			$thumb = $thumb_real;
		}
		$this->_kcfiles->sendImage($thumb);
	}

	public function expandAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir','');
		$data = array('dirs' => $this->_kcfiles->getDirs($this->_kcfiles->getUploadDir($dir,false)));
		$this->sendJson($data);
	}

	public function chdirAction()
	{

		$request = $this->getRequest();
		$dir = $request->getParam('dir','');
		$directory = $this->_kcfiles->getUploadDir($dir,false);
		if( !$this->_kcfiles->checkDir($directory) ){
			return $this->sendJson('error', 'Unknown error.');
		}
		else {
			$this->_kcfiles->setSessionDir($dir);
			$dirWritable = $this->_kcfiles->isWritable($directory);
			$files = $this->_kcfiles->getFiles($this->_kcfiles->getUploadDir(null,false),$dir);
			$data = array (
					'files' => $files,
					'dirWritable' => $dirWritable?'true':'false'
			);
			$this->sendJson($data);
		}
	}

	public function newdirAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$newDir = $request->getParam('newDir');
		$allowed = $this->_kcfiles->access['dirs']['create'];

		if( !isset($dir) || !isset($newDir) || !$allowed ){
			return $this->sendJson('error', 'Unknown error.');
		}
		$newDir = trim($newDir);
		if (!strlen($newDir)) {
			return $this->sendJson('error', "Please enter new folder name.");
		}

		if (preg_match('/[\/\\\\]/s', $newDir)){
			$this->sendJson('error', "Unallowable characters in folder name.");
			return ;
		}
		if (substr($newDir, 0, 1) == "."){
			$this->sendJson('error', "Folder name shouldn't begins with '.'");
			return ;
		}
		$dir1 = $this->_kcfiles->getUploadDir(array($dir,$newDir),false);
		$dir2 = $this->_kcfiles->getThumbDir(array($dir,$newDir),false);
		if (file_exists($dir1) || file_exists($dir2)) {
			return $this->sendJson('error', "A file or folder with that name already exists.");
		}
		$perm = $this->_kcfiles->dirPerms;
		if (!@mkdir($dir1, $perm) ) {
			return $this->sendJson('error','Cannot create {dir} folder.',array("{dir}"=>$newDir));
		}
		if (!@mkdir($dir2, $perm) ) {
			rmdir($dir1);
			return $this->sendJson('error','Cannot create {dir} folder.',array("{dir}"=>$newDir));
		}
		return $this->sendJson(true);
	}

	public function renamedirAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$newName = $request->getParam('newName');
		$allowed = $this->_kcfiles->access['dirs']['rename'];
		if( !isset($dir) || !isset($newName) ||!$allowed ){
			return $this->sendJson('error', 'Unknown error.');
		}
		if (!strlen($newName)){
			return $this->sendJson('error', "Please enter new folder name.");
		}

		if (preg_match('/[\/\\\\]/s', $newName)) {
			return $this->sendJson('error', 'Unallowable characters in folder name.');
		}

		if (substr($newName, 0, 1) == "."){
			return $this->sendJson('error', "Folder name shouldn't begins with '.'");
		}
		$pathOrig = $this->_kcfiles->getUploadDir($dir,false);
		$pathDest = $this->_kcfiles->getUploadDir($newName,false);
		if (!@rename($pathOrig, $pathDest)){
			$this->sendJson('error', "Cannot rename the folder.");
			return ;
		}
		// change the name of thumbs directory
		$pathOrig = $this->_kcfiles->getThumbDir($dir,false);
		$pathDest = $this->_kcfiles->getThumbDir($newName,false);
		if( is_dir($pathOrig)) {
			if( !@rename($pathOrig, $pathDest) ){
				// this should be log to the admin
				return $this->sendJson('error', 'Unknown error.');
			}
		}
		$this->sendJson('name',$newName);
	}

	public function deletedirAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$directory = $this->_kcfiles->getUploadDir($dir,false);
		$allowed = $this->_kcfiles->access['dirs']['delete'];

		if( !isset($dir)  || !$allowed || !$this->_kcfiles->checkDir($directory) ){
			return $this->sendJson('error', 'Unknown error.');
		}

		if (!$this->_kcfiles->isWritable($directory)){
			$this->sendJson('error', "Cannot delete the folder.");
		}

		$thumbDir = $this->_kcfiles->getThumbDir($dir,false);
		if (is_dir($thumbDir)) {
			$this->_kcfiles->prune($thumbDir);
		}

		$result = $this->_kcfiles->prune($directory, false);
		if( $result !== true ) {
			return $this->sendJson('error',"Failed to delete {count} files/folders.",array("{count}"=> $result));
		}
		$this->sendJson(true);
	}

	public function renameAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$oldName = $request->getParam('file');
		$newName = $request->getParam('newName');

		$directory = $this->_kcfiles->getUploadDir($dir,false);
		$filename_old = $this->_kcfiles->getUploadDir(array($dir,$oldName),false);
		$filename_new = $this->_kcfiles->getUploadDir(array($dir,$newName),false);
		$allowed = $this->_kcfiles->access['files']['rename'];
		// now check the new name
		if( is_file($filename_new)){
			return $this->sendJson('error', 'A file or folder with that name already exists.');
		}
		if( !isset($dir)  || !isset($oldName) || !isset($newName) || !$allowed ||
				!$this->_kcfiles->checkDir($directory) || !is_writable($filename_old) )
		{
			return $this->sendJson('error', 'Unknown error.');
		}

		if( rename($filename_old, $filename_new) ){
			// now rename the thumb
			$thumb_old = $this->_kcfiles->getThumbDir(array($dir,$oldName),false);
			$thumb_new = $this->_kcfiles->getThumbDir(array($dir,$newName),false);
			rename($thumb_old,$thumb_new);
		}
		$this->sendJson(true);
	}

	public function deleteAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$file = $request->getParam('file');
		$allowed = $this->_kcfiles->access['files']['delete'];
		$directory = $this->_kcfiles->getUploadDir($dir,false);

		$thumbs = $file;
		$this->_kcfiles->prependPath($this->_kcfiles->getThumbDir($dir), $thumbs, false);

		if( !isset($dir) || !isset($file) || !$allowed || !$this->_kcfiles->checkDir($directory)||
			!$this->_kcfiles->prependPath($this->_kcfiles->getUploadDir($dir), $file ) ||
			!is_writable( $file ))
		{
			return $this->sendJson('error', 'Unknown error.');
		}
		elseif ( @unlink($file) ) {
			@unlink($thumbs);
			$this->sendJson(true);
		}
	}

	public function copycbdAction($command='copy'){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$files = $request->getParam('files');
		$allowed = $this->_kcfiles->access['files']['copy'];
		$directory = $this->_kcfiles->getUploadDir($dir);

		if( !isset($dir) || !isset($files) || !$allowed || !$this->_kcfiles->checkDir($directory) ||
				!$this->_kcfiles->prependPath($this->_kcfiles->getUploadDir(null,false), $files)||
				!is_writable($this->_kcfiles->getUploadDir($dir,false)))
		{
			return $this->sendJson('error', 'Unknown error.');
		}

		foreach ($files as $fileOrig) {
			$fileDest = $this->_kcfiles->getUploadDir(array($dir,basename($fileOrig)),false);
			if( true != $command ( $fileOrig, $fileDest ) ) {
				return $this->sendJson('error', 'Unknown error.');
			}
		}
		$files = $request->getParam('files');
		foreach ($files as $fileOrig) {
			$thumbDest = $this->_kcfiles->getThumbDir(array($dir,basename($fileOrig)),false);
			$thumbOrig = $this->_kcfiles->getThumbDir(array($fileOrig),false);
			if( true != $command ( $thumbOrig, $thumbDest) ) {
				return $this->sendJson('error', 'Unknown error.');
			}
		}

		$this->sendJson(true);
	}

	public function movecbdAction(){
		$this->copycbdAction('rename');
	}

	public function removecbdAction(){
		$request = $this->getRequest();
		$files = $request->getParam('files');
		$allowed = $this->_kcfiles->access['files']['delete'];
		$this->_kcfiles->prependPath($this->_kcfiles->getUploadDir(null,false), $files);
		if( !isset($files) || !count($files) || !$allowed || 
			!$this->_kcfiles->existFile($files))
		{
			return $this->sendJson('error', 'Unknown error.');
		}
		$files = $request->getParam('files');
		foreach($files as $file) {
			if (substr($file, 0, 1) == ".")
				continue;
			$filepath = $this->_kcfiles->getUploadDir($file,false);
			$thumbpath = $this->_kcfiles->getThumbDir($file,false);
			if (!is_file($filepath)) {
				return $this->sendJson('error',"The file '{file}' does not exist.",array('{file}'=>$file));
			}
			elseif (!@unlink($filepath)) {
				return $this->sendJson('error',"Cannot delete '{file}'.",array('{file}'=>$file));
			}
			if (is_file($thumbpath))
				@unlink($thumbpath);
		}
		return $this->sendJson(true);
	}

	public function downloaddirAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$allowed = !$this->_kcfiles->denyZipDownload;
		if (!isset($dir) || !$allowed){
			return $this->sendRaw( 'Unknown error.');
		}
		$filename = basename($dir) . ".zip";
		$file = $this->_kcfiles->getTemporaryFileName($this->_kcfiles->getUploadDir(null,false), 'zip');
		$this->_kcfiles->zipFolder($this->_kcfiles->getUploadDir($dir,false), $file);
		$this->_kcfiles->sendZip($file, str_replace('"', "_", $filename), true );

	}

	public function downloadselectedAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$files = $request->getParam('files');
		$allowed = !$this->_kcfiles->denyZipDownload;
		$hiddens = $this->_kcfiles->filterHidden($files);
		$filespath = $this->_kcfiles->prepend($this->_kcfiles->getUploadDir($dir),$files);
		$readable = $this->_kcfiles->checkReadable($filespath);
		if (!isset($dir) || !isset($files) || !$allowed || $hiddens || !$readable){
			return $this->sendRaw( 'Unknown error.');
		}
		$filename = basename($dir) . ".zip";
		$file = $this->_kcfiles->getTemporaryFileName($this->_kcfiles->getUploadDir(null,false), 'zip');
		$this->_kcfiles->zipList($filespath, $file);
		$this->_kcfiles->sendZip($file, str_replace('"', "_", $filename), true  );

	}

	public function downloadclipboardAction(){
		$request = $this->getRequest();
		$files = $request->getParam('files');
		$allowed = !$this->_kcfiles->denyZipDownload;
		$hiddens = $this->_kcfiles->filterHidden($files);
		$filespath = $this->_kcfiles->prepend($this->_kcfiles->getUploadDir(),$files);
		$readable = $this->_kcfiles->checkReadable($filespath);
		if ( !isset($files) || !$allowed || $hiddens || !$readable){
			return $this->sendRaw( 'Unknown error.');
		}
		$file = $this->_kcfiles->getTemporaryFileName($this->_kcfiles->getUploadDir(null,false), 'zip');
		$this->_kcfiles->zipList($filespath, $file);
		$this->_kcfiles->sendZip($file, str_replace('"', "_", 'clipboard_'. basename($file)) , true );

	}

	public function styleAction()
	{
		$path = realpath(dirname(__FILE__).'/../views/scripts/kc/style.phtml');
		$mtime = @filemtime($path);

		$this->_kcfiles->setHeader('Content-Type', 'text/css',true)
		->setHeader('Cache-Control', 'public, max-age=3600')
		->setHeader('Pragma', 'public')
		->setHeader('Last-Modified',gmdate("D, d M Y H:i:s", $mtime) . " GMT");
		
		$request = $this->getRequest();
		$cacheDate = $request->getHeader('If-Modified-Since');
		if( is_string($cacheDate) )
		{
			$client_mtime = @strtotime($cacheDate);
			if( $client_mtime == $mtime)
			{
				return $response->setRawHeader('HTTP/1.1 304 Not Modified');
			}
		}
		$this->_helper->viewRenderer->setNoRender(false);
		$this->view->thumbWidth = $this->_kcfiles->thumbWidth;
		$this->view->thumbHeight = $this->_kcfiles->thumbHeight;
	}

	public function getjoinerAction()
	{
		//$os = PHP_OS;
		$path = $this->_kcfiles->normalize($this->_realpath."/js/browser");
		$this->view->files = $this->_kcfiles->getDirContent($path, array( 'types' => "file", 'pattern' => '/^.*\.js$/'));
		foreach ($this->view->files as $file) {
			$fmtime = filemtime($file);
			if (!isset($mtime) || ($fmtime > $mtime))
				$mtime = $fmtime;
		}

		$this->_kcfiles->setHeader('Content-Type', 'text/css',true)
		->setHeader('Cache-Control', 'public, max-age=3600')
		->setHeader('Pragma', 'public')
		->setHeader('Last-Modified',gmdate("D, d M Y H:i:s", $mtime) . " GMT");
		$request = $this->getRequest();
		$cacheDate = $request->getHeader('If-Modified-Since');
		if( is_string($cacheDate) )
		{
			$client_mtime = @strtotime($cacheDate);
			if( $client_mtime == $mtime)
			{
				return $response->setRawHeader('HTTP/1.1 304 Not Modified');
			}
		}
		$this->_helper->viewRenderer->setNoRender(false);
	}

	public function localizeAction()
	{
		$request = $this->getRequest();
		$language = $request->getParam('lng','en');
		$this->view->fields = null;
		$locale_applied = $this->view->translator->getLocale();

		$translation_dir = realpath(APPLICATION_PATH.self::DIR_LANGUAGES);

		// get the correct language
		$filename = $translation_dir.'/'.$locale_applied.'/kc.csv';
		if( !file_exists($filename) ){
			$filename = $translation_dir.'/en/kc.csv';
		}

		$mtime = @filemtime($filename);
		$request = $this->getRequest();
		$cacheDate = $request->getHeader('If-Modified-Since');
		$response = $this->getResponse();
		if( is_string($cacheDate) )	{
			$client_mtime = @strtotime($cacheDate);
			if( $client_mtime == $mtime)
			{
				return $response->setRawHeader('HTTP/1.1 304 Not Modified');
			}
		}
		// render

		if (($handle = fopen($filename, "r")) !== FALSE) {
			$data = array();
			while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
				$data[$row[0]] = $row[1];
			}
			fclose($handle);
		}
		$this->_helper->viewRenderer->setNoRender(false);
		$this->view->data = $data;
		$this->_kcfiles->setHeader('Content-Type','text/javascript',true)
		->setHeader('Cache-Control','public, max-age=3600')
		->setHeader('Pragma','public')
		->setHeader('Last-Modified',gmdate("D, d M Y H:i:s", $mtime) . " GMT");

	}

	public function createtranslationsAction(){
		$this->_helper->viewRenderer->setNoRender(false);
		$translation_dir = realpath(dirname(__FILE__).'/../models/'.self::DIRECTORY_LANGUAGES);
		$request = $this->getRequest();
		$language = $request->getParam('language','en');

		if ( $language != 'en' )
			include_once APPLICATION_PATH."/models/kcLanguages/$language.php";
		else
			include_once APPLICATION_PATH."/models/kcLanguages/bg.php";
		$class = new KcTranslation();
		$items = $class->lang;
		$en = array_keys($items);


		unset($items[$en[0]]);
		unset($items[$en[1]]);
		unset($items[$en[2]]);
		unset($items[$en[3]]);
		unset($items[$en[4]]);
		unset($items[$en[5]]);
		unset($en[0]);
		unset($en[1]);
		unset($en[2]);
		unset($en[3]);
		unset($en[4]);
		unset($en[5]);

		$this->view->keys = $en;
		if( $language == 'en' )
		{
			foreach ($items as $key => $item ){
				$items[$key]=$key;

			}

		}
		$this->view->data = $items;

	}

	public function browseAction()
	{
		$this->_helper->viewRenderer->setNoRender(false);
		$request = $this->getRequest();

		$this->_kcfiles->setHeader('X-UA-Compatible', 'chrome=1',true)
		->setHeader('Content-Type', 'text/html');

		$locale=new Zend_Locale();
		$this->view->lang = $locale->getLanguage();
	
		$theme = $request->getParam('theme',$this->_kcfiles->theme);
		if( file_exists($this->_realpath."/themes/{$theme}/init.js" ))
		{
			$this->view->init_theme = "themes/{$this->_kcfiles->theme}/init.js";
		}
		else 
		{
			$this->view->init_theme = "themes/{$theme}/init.js";
		}
		$this->view->publicPath = $this->_kcfiles->kcPath;

		$browser = array();
		$this->view->theme = $theme;

		$this->view->version = '2.51';
		
		$this->view->cromeFrame = 'false';
		$this->view->supportZip = $this->_kcfiles->denyZipDownload?'false':'true';
		 
		$this->view->check4Update = (!$this->_kcfiles->denyUpdateCheck && (ini_get("allow_url_fopen") || function_exists("http_get") || function_exists("curl_init") || function_exists('socket_create'))) ? "true" : "false";
		
		//$kcsession = Zend_Session::namespaceGet('KcFinder');
		$this->view->dir = $this->_kcfiles->getSessionDir();
		$this->view->uploadURL = $this->_kcfiles->uploadURL;
		$this->view->thumbsDir = $this->_kcfiles->thumbsDir;
		
		$opener = $request->getParam('opener');
		if( !is_null($opener)) {
			$this->view->setOpenerName = true;
			$this->view->openerName = $opener;
			if($opener == 'tinymce')
			{
				$this->view->isTinyMCE = true;
				$this->view->tinyMCEpath = $this->_kcfiles->tinyMCEpath;
			}
		}
		
		$cms = $request->getParam('cms');
		if($cms != 'drupal')
		{
			$cms = null;
		}
		$this->view->cms = $cms;
		
		$CKEditorFuncNum = $request->getParam('CKEditorFuncNum');
		if( !is_null($CKEditorFuncNum)) {
			$this->view->isOpenedByCk = true;
			$this->view->CKEditorFuncNum = $CKEditorFuncNum;
		}
		
		$access = $this->_kcfiles->access;
		foreach ($access as $key1 => $par1 ){
			foreach ($par1 as $key=>$par){
				$access[$key1][$key] = (bool)$par;
			}
		}
		
		$this->view->access = Zend_Json::encode($access);
		$front = Zend_Controller_Front::getInstance();

		$kuki['path'] = '/';
		$kuki['prefix'] = 'ZFKC_';
		$kuki['server'] = $request->getServer('SERVER_NAME');

		if( empty($this->_type))
			throw new Zend_Exception('Invalid request');
		$this->view->type = $this->_type;
		$this->view->kuki = $kuki;
		$this->view->browser = $browser;
		

	}

	public function browseinitAction()
	{
		if( empty($this->_type))
			throw new Zend_Exception('Invalid request');
		
		$mtime = @filemtime(__FILE__);
		$dir = $this->_kcfiles->removeTypeFromPath($this->_kcfiles->getSessionDir());
		$this->_kcfiles->setHeader('Content-Type', 'text/plain',true);
		$data = $this->_kcfiles->init_browser($this->_kcfiles->getUploadDir($this->_type,false),$dir);
		$this->sendJson($data);
	}

	public function uploadAction(){

		if( !$this->_kcfiles->access['files']['upload'] ) {
			return $this->sendRaw( 'Unknown error.');
		}
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		if( !$this->_kcfiles->isWritable($this->_kcfiles->getUploadDir($dir,false)) ){
			return $this->sendRaw( "Cannot access or write to upload folder.") ;

		}

		$file = $request->getParam('file');

		$upload = new Zend_File_Transfer_Adapter_Http();
		$files = $upload->getFileInfo();
		$this->view->answer =$this->view->translator->_("Unknown error.") ;
		$maxsize = $this->_kcfiles->maxsize;
		$return = array();
		foreach ($files as $file ) {
			$dest_file 	= $this->_kcfiles->getUploadDir(array($dir,$file['name']),false);//$this->_uploadDir.'/'.$dir.'/'.$file['name'];
			$dest_thumb = $this->_kcfiles->getThumbDir(array($dir,$file['name']),false);//$this->_uploadDir.'/.thumb'.$dir.'/'.$file['name'];

			if( is_file($dest_file)){
				return $this->sendRaw("You can't upload such files.") ;
			}
			//"The uploaded file exceeds {size} bytes."
			if( $file['size'] > $maxsize ){
				return $this->sendRaw("The uploaded file exceeds {size} bytes.",array('{size}' => $maxsize));
			}
			$upload->setDestination($this->_kcfiles->getUploadDir($dir,false));
			if (!$upload->receive()) {
				return $this->sendRaw("You can't upload such files.");
			}
			$this->_kcfiles->makeThumb($dest_file, $dest_thumb);
			$return[] = "/" . basename($file['name']);

		}
		$this->sendRaw($return) ;
	}

	protected function sendJson($key, $data=null, $option=null ) {
		$this->_kcfiles->setHeader('Content-Type', 'application/json');
		if( is_bool( $key ) ) {
			$this->_helper->json->sendJson(array('result' => $key ));
		}
		elseif (is_array($key)) {
			$this->_helper->json->sendJson($key);
		}
		else {
			$message = $this->view->translator->_($data) ;
			if( is_array($option)) {
				foreach ($option as $key => $value )
					$message = str_replace("$key", $value, $message);
			}
			$this->_helper->json->sendJson(array($key => $message));
		}
	}

	protected function sendRaw($data_array, $option=null){
		if(!is_array($data_array)) {
			$data_array = array($data_array);
		}
		foreach ($data_array as $data) {
			$message = $this->view->translator->_($data) ;
			if(is_array($option)){
				foreach ($option as $key => $value )
					$message = str_replace("$key", $value, $message);
			}
			echo $message;
		}

	}
}



