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


	public function init()
	{
		/* Initialize action controller here */
		$this->_config = new Zend_Config_Ini(APPLICATION_PATH."/configs/KcConfig.ini", 'browser' );

		$this->_realpath = Application_Model_kclib_Path::normalize(PUBLIC_PATH.'/'.$this->_config->kcPath);
		$this->_uploadDir = PUBLIC_PATH.$this->_config->uploadURL;
		$this->_uploadUrl = $this->_config->imagesDir;
		
		$request = $this->getRequest();

		// define types we're working on
		$this->_types = $request->getParam('type','images');

		Application_Model_kcBrowser::$config = $this->_config->toArray();
		if( !is_int(Application_Model_kcBrowser::$config['dirPerms']) )
		{
			Application_Model_kcBrowser::$config['dirPerms'] = octdec(Application_Model_kcBrowser::$config['dirPerms']);
			Application_Model_kcBrowser::$config['filePerms'] = octdec(Application_Model_kcBrowser::$config['filePerms']);
		}

		$layout = Zend_Layout::getMvcInstance();
		$layout->disableLayout();


	}

	public function indexAction()
	{
		// action body
	}

	public function testAction()
	{
		// action body
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
		$browser['supportZip'] = 'false'; //class_exists('ZipArchive') && !$this->config['denyZipDownload']) ? "true" : "false"
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

	public function chdirAction()
	{

		$request = $this->getRequest();
		$dir = $request->getParam('dir','');

		try {
			$directory = Application_Model_kcBrowser::checkDir($this->_uploadDir, $dir);
		} catch (Exception $e){

			$message = $e->getMessage();
			/*
			 * TODO: store the message in log
			 */
			$this->_helper->json->sendJson(array('error' => $this->view->translator->_('Unknown error.')));
			return ;
		}
		$this->setSessionDir($dir);
		$dirWritable = Application_Model_kclib_Dir::isWritable($directory);
		$files = Application_Model_kcBrowser::getFiles($this->_uploadDir,$dir);
		$data = array (
			'files' => $files,
			'dirWritable' => $dirWritable?'true':'false'			
		);
		$this->_helper->json->sendJson($data);
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
		$data = array('dirs' => Application_Model_kcBrowser::getDirs($this->_uploadDir.'/'.$dir));
		$this->_helper->json->sendJson($data);
	}
	
	public function renameAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir');
		$oldName = $request->getParam('file');
		
		$newName = $request->getParam('newName');
		$allowed = $this->_config->access->files->rename;
		
		if( !isset($dir) || !isset($oldName) || !isset($newName) || !$allowed ) {
			$this->_helper->json->sendJson(	array('error' => $this->view->translator->_('Unknown error.')) );
			return;
		}
		
		try {
			$directory = Application_Model_kcBrowser::checkDir($this->_uploadDir, $dir);
			$filename = Application_Model_kcBrowser::existFile($this->_uploadDir.'/'.$dir, $oldName);
		} catch (Exception $e){
			$message = $e->getMessage();
			/*
			 * TODO: store the message in log
			*/
			$this->_helper->json->sendJson(array('error' => $this->view->translator->_('Unknown error.')));
			return ;
		}
		if( !is_writable($filename) ){
			$this->_helper->json->sendJson(array('error' => $this->view->translator->_('Unknown error.')));
			return;
		}
		// now check the new name
		$new = $directory.'/'.$newName;
		if( is_file($new)){
			$this->_helper->json->sendJson(array('error' => $this->view->translator->_('A file or folder with that name already exists.')));
			return;
		}
		
		if( rename($filename, $new) ){
			// now rename the thumb
			$thumb_old = $this->_uploadDir.'/.thumbs/'.$dir.'/'.$oldName;	
			$thumb_new = $this->_uploadDir.'/.thumbs/'.$dir.'/'.$newName	;
			rename($thumb_old,$thumb_new);
		}
			
		$data = array();
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



