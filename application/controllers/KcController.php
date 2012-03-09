<?php

class KcController extends Zend_Controller_Action
{
	const DIRECTORY_LANGUAGES	= 'kcLanguages';
	const DIR_LANGUAGES = '/language';
	protected $_kcfinderDir;
	protected $_realpath;
	protected $_uploadDir;
	protected $_uploadUrl;
	const TYPE = '/images';
	protected $_types;
	protected $_config;
	protected $_kcfiles;


	public function init()
	{
		/* Initialize action controller here */
		$this->_config = new Zend_Config_Ini(APPLICATION_PATH."/configs/KcConfig.ini", 'browser' );

		$this->_realpath = Application_Model_kclib_Path::normalize(PUBLIC_PATH.'/'.$this->_config->kcPath);
		$this->_uploadDir = PUBLIC_PATH.$this->_config->uploadURL;
		$this->_uploadUrl = $this->_config->imagesDir;
		$this->_kcfiles = $this->_helper-> getHelper('Kcfiles')->Config($this->_config);
		
		$request = $this->getRequest();

		// define types we're working on
		$this->_types = $request->getParam('type','images');

		Application_Model_kcBrowser::$config = $this->_config->toArray();
		if( !is_int(Application_Model_kcBrowser::$config['dirPerms']) )
		{
			Application_Model_kcBrowser::$config['dirPerms'] = octdec(Application_Model_kcBrowser::$config['dirPerms']);
			Application_Model_kcBrowser::$config['filePerms'] = octdec(Application_Model_kcBrowser::$config['filePerms']);
		}
		$this->_kcfiles->makeThumb(
				'/home/claudio/public_html/zfkc/public/upload/images/p1020680.jpg',
				'/home/claudio/public_html/zfkc/public/upload/.thumbs/images/p1020680.jpg' );
		

		$layout = Zend_Layout::getMvcInstance();
		$layout->disableLayout();
	}
	
	public function indexAction()
	{
		// action body
	}
	
	public function thumbAction(){
		//$contextSwitch =$this->_helper->contextSwitch();
		//$contextSwitch->initContext();
	
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		$dir = $request->getParam('dir',null);
		$file_name = $request->getParam('file',null);
		$default = true;
		$lastcode = null;
	
		if( isset($dir) && isset($file_name) )
		{
			$file= $dir.'/'.$file_name;
	
			// check existence
			try {
				$file_real = Application_Model_kcBrowser::existFile(
						$this->_uploadDir,$file);
				$thumb_real = Application_Model_kcBrowser::existFile(
						$this->_uploadDir.'/'.$this->_config->thumbsDir, $file);
				unset($default);
			} catch (Exception $e){
				$message = $e->getMessage();
				/**
				 * TODO: check if file exist and thumb doesn't exist
				 * and sent this case to the log
				 */
			}
		}
	
		if( isset($default ) )
		{
			$ext = Application_Model_kclib_File::getExtension($file_name);
			$thumb = "{$this->_realpath}/themes/{$this->_config->theme}/img/files/big/$ext.png";
		}
		else
		{
			$thumb = $thumb_real;
		}
		$ext = strtolower( Application_Model_kclib_File::getExtension($thumb) );
		if( $ext != 'png' )
			$ext = 'jpeg';
	
		$response = $this->getResponse();
		$response->setHeader('Content-Type', "image/$ext",true);
		readfile($thumb);
	
	}
	
	public function expandAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir','');
		$data = array('dirs' => $this->_kcfiles->getDirs($this->_uploadDir.'/'.$dir));
		$this->_helper->json->sendJson($data);
	}
	
	public function chdirAction()
	{
	
		$request = $this->getRequest();
		$dir = $request->getParam('dir','');
	
		try {
			$directory = $this->_kcfiles->checkDir($this->_uploadDir, $dir);
		} catch (Exception $e){
	
			$message = $e->getMessage();
			/*
			 * TODO: store the message in log
			*/
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return ;
		}
		$this->setSessionDir($dir);
		$dirWritable = $this->_kcfiles->isWritable($directory);
		$files = Application_Model_kcBrowser::getFiles($this->_uploadDir,$dir);
		$data = array (
				'files' => $files,
				'dirWritable' => $dirWritable?'true':'false'
		);
		$this->_helper->json->sendJson($data);
	}
	
	public function newdirAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$newDir = $request->getParam('newDir');
		$allowed = $this->_config->access->dirs->create;
		if( !isset($dir) || !isset($newDir) || !$allowed ){
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return ;
		}
		$newDir = trim($newDir);
		if (!strlen($newDir)) {
			$this->_helper->json->sendJson(array('error' => "Please enter new folder name."));
			return ;
		}
	
		if (preg_match('/[\/\\\\]/s', $newDir)){
			$this->_helper->json->sendJson(array('error' => "Unallowable characters in folder name."));
			return ;
		}
		if (substr($newDir, 0, 1) == "."){
			$this->_helper->json->sendJson(array('error' => "Folder name shouldn't begins with '.'"));
			return ;
		}
		$dir1 = $this->_uploadDir.'/'.$dir.'/'.$newDir;
		if (file_exists($dir1)) {
			$this->_helper->json->sendJson(array('error' => "A file or folder with that name already exists."));
			return ;
		}
		if (!@mkdir($dir1,Application_Model_kcBrowser::$config['dirPerms'])) {
			$msg = $this->view->translator->_("Cannot create {dir} folder.");
			$msg = str_replace("{dir}", $newDir, $msg);
			$data = array('error' => $msg);
		}
		else{
			$data = array('result'=>true);
		}
		$this->_helper->json->sendJson($data);
	
		return true;
	}
	public function renamedirAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$newName = $request->getParam('newName');
		$allowed = $this->_config->access->dirs->rename;
		if( !isset($dir) || !isset($newName) ||!$allowed ){
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return ;
		}
		if (!strlen($newName)){
			$this->_helper->json->sendJson(array('error' => "Please enter new folder name."));
			return ;
		}
		
		if (preg_match('/[\/\\\\]/s', $newName)) {
			$this->_helper->json->sendJson(array('error' => 'Unallowable characters in folder name.'));
			return ;
		}
		
		if (substr($newName, 0, 1) == "."){
			$this->_helper->json->sendJson(array('error' => "Folder name shouldn't begins with '.'"));
			return ;
		}
		$pathOrig = $this->_uploadDir .'/'. $dir;
		$pathDest = $this->_uploadDir .'/'. dirname($dir) . "/$newName";
		if (!@rename($pathOrig, $pathDest)){
			$this->_helper->json->sendJson(array('error' => "Cannot rename the folder."));
			return ;
		}
		// change the name of thumbs directory
		$pathOrig = $this->_uploadDir .'/.thumbs/'. $dir;
		if( is_dir($pathOrig)) {
			$pathDest = $this->_uploadDir .'/.thumbs/'. dirname($dir) . "/$newName";
			if( !@rename($pathOrig, $pathDest) ){
				// this should be log to the admin
				$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
				return;
			}
		}
		
		$data = array('name'=>$newName);
		$this->_helper->json->sendJson($data);
	}
		
	public function deletedirAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		
		$allowed = $this->_config->access->dirs->delete;
		if( !isset($dir)  ||!$allowed ){
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return ;
		}
		try {
			$directory = Application_Model_kcBrowser::checkDir($this->_uploadDir, $dir);
		} catch (Exception $e){
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return ;
		}
		
		if (!Application_Model_kclib_Dir::isWritable($directory)){
			$this->_helper->json->sendJson(array('error' => "Cannot delete the folder."));
		}
		
		$thumbDir = $this->_uploadDir .'/.thumbs/'. $dir;
		if (is_dir($thumbDir)) {
			Application_Model_kclib_Dir::prune($thumbDir);
		}
		
		$result = !Application_Model_kclib_Dir::prune($directory, false);
		if( $result === true ) {
			$data = array('result'=>true);
		} 
		else {
			$data = array(	'error'	=>	"Failed to delete {count} files/folders.",
							'count' => 	count($result)	);
		}
		$this->_helper->json->sendJson($data);
	}
	public function renameAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$oldName = $request->getParam('file');
	
		$newName = $request->getParam('newName');
		$allowed = $this->_config->access->files->rename;
	
		if( !isset($dir) || !isset($oldName) || !isset($newName) || !$allowed ) {
			$this->_helper->json->sendJson(	array('error' => 'Unknown error.')) ;
			return;
		}
	
		try {
			$directory = Application_Model_kcBrowser::checkDir($this->_uploadDir, $dir);
			$filename = Application_Model_kcBrowser::existFile($this->_uploadDir.'/'.$dir, $oldName);
		} catch (Exception $e){
			$message = $e->getMessage();
			/*
			 * TODO: log the message
			*/
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return ;
		}
		if( !is_writable($filename) ){
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return;
		}
		// now check the new name
		$new = $directory.'/'.$newName;
		if( is_file($new)){
			$this->_helper->json->sendJson(array('error' => 'A file or folder with that name already exists.'));
			return;
		}
	
		if( rename($filename, $new) ){
			// now rename the thumb
			$thumb_old = $this->_uploadDir.'/.thumbs/'.$dir.'/'.$oldName;
			$thumb_new = $this->_uploadDir.'/.thumbs/'.$dir.'/'.$newName	;
			rename($thumb_old,$thumb_new);
		}
			
		$data = array('result'=>true);
		$this->_helper->json->sendJson($data);
	}
	
	public function deleteAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$file = $request->getParam('file');
		$allowed = $this->_config->access->files->delete;
		if( !isset($dir) || !isset($file) || !$allowed ) {
			$this->_helper->json->sendJson(	array('error' => 'Unknown error.') );
			return;
		}
		try {
			$directory = Application_Model_kcBrowser::checkDir($this->_uploadDir, $dir);
			$filename = Application_Model_kcBrowser::existFile($this->_uploadDir.'/'.$dir, $file);
		} catch (Exception $e){
			$message = $e->getMessage();
			/*
			 * TODO: log the message
			*/
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return ;
		}
	
		if( !is_writable($filename) ){
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return;
		}
		if( unlink($filename) ) {
			unlink($this->_uploadDir.'/.thumbs/'.$dir.'/'.$file);
		}
		$this->_helper->json->sendJson(array('result'=>true));
	
	}
	
	public function copycbdAction($doCopy=true){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$files = $request->getParam('files');
		$allowed = $this->_config->access->files->copy;
	
		try {
			if( !isset($dir) || !isset($files) || !$allowed )
				throw new Exception('Invalid parameters!',-1);
			$directory = Application_Model_kcBrowser::checkDir($this->_uploadDir, $dir);
			$filename = Application_Model_kcBrowser::existFile($this->_uploadDir, $files);
		} catch (Exception $e){
			$message = $e->getMessage();
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return ;
		}
	
		if( !is_writable($this->_uploadDir.'/'.$dir) ){
			// this should be log to the admin
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return;
		}
		$errors = false;
		$command = $doCopy? 'copy': 'rename';
		foreach ($filename as $fileOrig) {
			$fileDest = $this->_uploadDir.'/'.$dir.'/'.basename($fileOrig);
			if( true != $command ( $fileOrig, $fileDest ) ) {
				$errors=true;
			}
	
		}
		foreach ($files as $fileOrig) {
			$thumbDest = $this->_uploadDir.'/.thumbs/'.$dir.'/'.basename($fileOrig);
			$thumbOrig = $this->_uploadDir.'/.thumbs/'.$fileOrig;
			if( true != $command ( $thumbOrig, $thumbDest) ) {
				$errors=true;
			}
		}
		if($errors)	{
			$data = array('error' => 'Unknown error.');
		}
		else {
			$data = array('result'=>true);
		}
		$this->_helper->json->sendJson($data);
	}
	
	public function movecbdAction(){
		$this->copycbdAction(false);
	}
	
	public function removecbdAction(){
		$request = $this->getRequest();
		$files = $request->getParam('files');
		$allowed = $this->_config->access->files->delete;
		try {
			if( !isset($files) || !count($files) || !$allowed )
				throw new Exception('Invalid parameters!',-1);
			Application_Model_kcBrowser::existFile($this->_uploadDir, $files);
		} catch (Exception $e){
			$message = $e->getMessage();
			$this->_helper->json->sendJson(array('error' => 'Unknown error.'));
			return ;
		}
		
		$error = array();
		foreach($files as $file) {
			$file = Application_Model_kclib_Path::normalize($file);
			if (substr($file, 0, 1) == ".") 
				continue;
			//$type = explode("/", $file);
			//$type = $type[0];
			//if ($type != $this->type) continue;
			$filepath = $this->_uploadDir."/$file";
			$thumbpath = $this->_uploadDir."/.thumbs/$file";
			$base = basename($file);
			$replace = array('file' => $base);
			if (!is_file($filepath))
				$error[$filepath] = "The file '{file}' does not exist.";
			elseif (!@unlink($filepath))
				$error[$filepath] = "Cannot delete '{file}'.";
			if (is_file($thumbpath)) 
				@unlink($thumbpath);
		}
		if (count($error)) {
			$return = array();
			foreach ($error as $file => $val) {
				$message = $this->view->translator($val);
				$return[] = str_replace("{file}", $file, $message);
			}
			return $this->_helper->json->sendJson(array('error' => $return));
		}
		return $this->_helper->json->sendJson(array('result' => true));
	}
	
	public function downloaddirAction(){
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$allowed = !$this->_config->access->files->denyZipDownload;
		if (!isset($dir) || !$allowed){
			return $this->_helper->json->sendJson(array('error' => "Unknown error."));
		}
		$filename = basename($dir) . ".zip";
		$file = $this->_kcfiles->getTemporaryFileName($this->_uploadDir, 'zip');
		$this->_kcfiles->zipFolder($this->_uploadDir.DIRECTORY_SEPARATOR.$dir, $file);
		$response = $this->getResponse();
		$response->clearAllHeaders();
		$response->setHeader('Content-Type', 'application/x-zip');
		$response->setHeader('Content-Disposition', 'attachment; filename="' . str_replace('"', "_", $filename) . '"');
		$response->setHeader('Content-Length',filesize($file));
		readfile($file);
		unlink($file);
	}
	
	public function downloadselectedAction(){
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$files = $request->getParam('files');
		$allowed = !$this->_config->access->files->denyZipDownload;
		$hiddens = $this->_kcfiles->filterHidden($files);
		$filespath = $this->_kcfiles->prepend($this->_uploadDir.'/'.$dir.'/',$files);
		$readable = $this->_kcfiles->checkReadable($filespath);
		if (!isset($dir) || !isset($files) || !$allowed || $hiddens || !$readable){
			return $this->_helper->json->sendJson(array('error' => "Unknown error."));
		}
		$filename = basename($dir) . ".zip";
		$file = $this->_kcfiles->getTemporaryFileName($this->_uploadDir, 'zip');
		$this->_kcfiles->zipList($filespath, $file);
		$response = $this->getResponse();
		$response->clearAllHeaders();
		$response->setHeader('Content-Type', 'application/x-zip');
		$response->setHeader('Content-Disposition', 'attachment; filename="' . str_replace('"', "_", $filename) . '"');
		$response->setHeader('Content-Length',filesize($file));
		readfile($file);
		unlink($file);
	}
	
	public function downloadclipboardAction(){
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		$files = $request->getParam('files');
		$allowed = !$this->_config->access->files->denyZipDownload;
		$hiddens = $this->_kcfiles->filterHidden($files);
		$filespath = $this->_kcfiles->prepend($this->_uploadDir.'/',$files);
		$readable = $this->_kcfiles->checkReadable($filespath);
		if ( !isset($files) || !$allowed || $hiddens || !$readable){
			return $this->_helper->json->sendJson(array('error' => "Unknown error."));
		}
		//$filename = basename($dir) . ".zip";
		$file = $this->_kcfiles->getTemporaryFileName($this->_uploadDir, 'zip');
		$this->_kcfiles->zipList($filespath, $file);
		$response = $this->getResponse();
		$response->clearAllHeaders();
		$response->setHeader('Content-Type', 'application/x-zip');
		$response->setHeader('Content-Disposition', 'attachment; filename="clipboard_' . basename($file) . '"');
		$response->setHeader('Content-Length',filesize($file));
		readfile($file);
		unlink($file);
	}
	
	public function styleAction()
	{
		$path = realpath(dirname(__FILE__).'/../views/scripts/kc/style.phtml');
		$mtime = @filemtime($path);

		$response = $this->getResponse();
		$response->setHeader('Content-Type', 'text/css',true);
		$response->setHeader('Cache-Control', 'public, max-age=3600',true);
		$response->setHeader('Pragma', 'public',true);

		$response->setHeader('Last-Modified',gmdate("D, d M Y H:i:s", $mtime) . " GMT");
		$request = $this->getRequest();
		$cacheDate = $request->getHeader('If-Modified-Since');
		if( is_string($cacheDate) )
		{
			$client_mtime = @strtotime($cacheDate);
			if( $client_mtime == $mtime)
			{
				$response->setRawHeader('HTTP/1.1 304 Not Modified');
				$this->_helper->viewRenderer->setNoRender();
				return;
			}
		}
		$this->view->thumbWidth = 100;
		$this->view->thumbHeight = 100;
	}

	public function getjoinerAction()
	{
		//$os = PHP_OS;

		$path = Application_Model_kclib_Path::normalize($this->_realpath."/js/browser");
		$this->view->files = Application_Model_kclib_Dir::content($path, array( 'types' => "file", 'pattern' => '/^.*\.js$/'));
		foreach ($this->view->files as $file) {
			$fmtime = filemtime($file);
			if (!isset($mtime) || ($fmtime > $mtime))
			$mtime = $fmtime;
		}

		$response = $this->getResponse();
		$response->setHeader('Content-Type', 'text/css',true);
		$response->setHeader('Cache-Control', 'public, max-age=3600',true);
		$response->setHeader('Pragma', 'public',true);

		$response->setHeader('Last-Modified',gmdate("D, d M Y H:i:s", $mtime) . " GMT");
		$request = $this->getRequest();
		$cacheDate = $request->getHeader('If-Modified-Since');
		if( is_string($cacheDate) )
		{
			$client_mtime = @strtotime($cacheDate);
			if( $client_mtime == $mtime)
			{
				$response->setRawHeader('HTTP/1.1 304 Not Modified');
				$this->_helper->viewRenderer->setNoRender();
				return;
			}
		}
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
				$response->setRawHeader('HTTP/1.1 304 Not Modified');
				$this->_helper->viewRenderer->setNoRender();
				return;
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
		$this->view->data = $data;
		$response->clearHeader('Expires');
		$response->setHeader('Content-Type', "text/javascript; charset=utf-8",true);
		$response->setHeader('Cache-Control', 'public, max-age=3600',true);
		$response->setHeader('Pragma', 'public',true);
		$response->setHeader('Last-Modified',gmdate("D, d M Y H:i:s", $mtime) . " GMT",true);

	}

	public function createtranslationsAction(){

		$translation_dir = realpath(dirname(__FILE__).'/../models/'.self::DIRECTORY_LANGUAGES);
		//$translation_dir = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.self::DIRECTORY_LANGUAGES;
		// get the correct language
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

		
		
/*		
		$this->render();
		$response = $this->getResponse();
		$data = $response->getBody();
		$directory = APPLICATION_PATH.'/language/'.$language;
		$destination = $directory.'/kc.csv';
		if(!is_dir($directory)) {
			mkdir($directory);
			chmod($directory, 0775);
		}	
		
		$file = fopen($destination,'w+');
		fwrite($file,$data);
		fclose($file);
		chmod($destination, 0664);
*/		
	}

	public function browseAction()
	{

		$request = $this->getRequest();
		$params = $request->getParams();

		if( isset($this->_config->session) )
		{
			Zend_Session::setOptions($this->_config->session->toArray());
		}
		$this->view->title = 'demo';
		$locale=new Zend_Locale();
		$language = $locale->getLanguage();
		$this->view->language = $language;
		if( isset($params['theme']) && file_exists($this->_realpath."/themes/{$params['theme']}" ) )
		{
			$theme = $params['theme'];
		}
		else
		{
			$theme = $this->_config->theme;
		}

		if( file_exists($this->_realpath."/themes/{$theme}/init.js" ))
		{
			$this->view->init_theme = "themes/{$theme}/init.js";
		}

		$browser = array();
		$browser['theme'] = $theme;

		$browser['version'] = '2.51';
		$browser['tinyMCE'] = null;
		$browser['tinyMCEpath'] = null;
		$browser['cromeFrame'] = 'false';
		$browser['supportZip'] = 'true'; //class_exists('ZipArchive') && !$this->config['denyZipDownload']) ? "true" : "false"
		$browser['check4Update'] = 'false'; //((!isset($this->config['denyUpdateCheck']) || !$this->config['denyUpdateCheck']) && (ini_get("allow_url_fopen") || function_exists("http_get") || function_exists("curl_init") || function_exists('socket_create'))) ? "true" : "false"
		$browser['type'] = 'images';
		$kcsession = Zend_Session::namespaceGet('KcFinder');
		$browser['dir'] = 'images/public';//Admin_Model_Kclib_Text::jsValue($kcsession['dir']);
		$browser['uploadURL'] = $this->_config->uploadURL;
		$browser['thumbsDir'] = $this->_config->thumbsDir;
		$browser['setOpener'] = false;
		$browser['openerName'] = '';
		$browser['isOpenedByCk'] = false; //isset($this->opener['CKEditor']['funcNum']) && preg_match('/^\d+$/', $this->opener['CKEditor']['funcNum'])
		$browser['funcNumCkEditor'] = '';
		$browser['openerName'] = null;
		$browser['cms'] = null;
		$access = $this->_config->access->toArray();
		foreach ($access as $key1 => $par1 ){
			foreach ($par1 as $key=>$par){
				if( $par )  {
					$access[$key1][$key] = true;
				}
				else {
					$access[$key1][$key] = false;
				}
			}

		}

			
		$browser['access'] = Zend_Json::encode($access);
		//'{"files":{"upload":true,"delete":true,"copy":true,"move":true,"rename":true},"dirs":{"create":true,"delete":true,"rename":true}}';
		$front = Zend_Controller_Front::getInstance();


		$kuki['domain'] = 'zfkc.local';
		$kuki['path'] = '/';
		$kuki['prefix'] = 'ZFKC_';
		$this->view->type = 'images';
		$this->view->kuki = $kuki;
		$this->view->browser = $browser;
		$this->view->publicPath = $this->_config->kcPath;

	}

	public function browseinitAction()
	{
		
		$mtime = @filemtime(__FILE__);
		$uploadDir = $this->_uploadDir;
		$typeDir = $this->_uploadDir.self::TYPE;
		
		$response = $this->getResponse();
		$response->setHeader('Content-Type', 'text/plain; charset=utf-8',true);
		
		$data = Application_Model_kcBrowser::act_init($typeDir,$this->getSessionDir());
		$this->_helper->json->sendJson($data);
	}

	
	
	protected function getSessionDir(){

		$zf_kceditor = new Zend_Session_Namespace('zf_kceditor');
		if( !isset($zf_kceditor->sessionDir) ) {
			$sessionDir = trim($this->_config->imagesDir,'/');
			$zf_kceditor->sessionDir = $sessionDir;
		}
		return $zf_kceditor->sessionDir;
	}

	protected function setSessionDir($dir){

		$zf_kceditor = new Zend_Session_Namespace('zf_kceditor');
		$zf_kceditor->sessionDir = $dir;
	}
	
}



