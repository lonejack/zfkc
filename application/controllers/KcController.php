<?php

class KcController extends Zend_Controller_Action
{
	const	DIRECTORY_LANGUAGES	= 'kcLanguages';
	protected $_kcfinderDir;
	protected $_realpath;
	protected $_uploadDir;
	protected $_uploadUrl;
	const TYPE = '/images';
	protected $_imagesAbsolutePath;
	protected $_imagesRelativePath;
	protected $_types;
	protected $_config;


	public function init()
	{
		/* Initialize action controller here */

		$this->_config = new Zend_Config_Ini(APPLICATION_PATH."/configs/KcConfig.ini", 'browser' );
		//$this->_config->dirPerms = octdec($this->_config->dirPerms);
		//$this->_config->filePerms = octdec($this->_config->filePerms);
		
		$this->_realpath = Application_Model_kclib_Path::normalize(PUBLIC_PATH.'/'.$this->_config->kcPath);
		$this->_uploadDir = PUBLIC_PATH.$this->_config->uploadURL;
		$this->_uploadUrl = $this->_config->imagesDir;
		$this->_imagesAbsolutePath = $this->_config->publicPath.$this->_config->imagesDir;
		$this->_imagesRelativePath = $this->_config->imagesDir;
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

	public function getcssimageAction()
	{
		$mtime = @filemtime(__FILE__);
		$headers = Application_Model_kclib_HttpCache::checkMTime($mtime);
		$headers[] = header("Content-Type: text/css");
		$this->view->headers = $headers;
		$this->view->thumbWidth = 100;
		$this->view->thumbHeight = 100;
	}

	public function getjoinerAction()
	{
		$os = PHP_OS;

		$path = Application_Model_kclib_Path::normalize($this->_realpath."/js/browser");

		$this->view->files = Application_Model_kclib_Dir::content($path, array( 'types' => "file", 'pattern' => '/^.*\.js$/'));
		foreach ($this->view->files as $file) {
			$fmtime = filemtime($file);
			if (!isset($mtime) || ($fmtime > $mtime))
			$mtime = $fmtime;
		}
		$this->view->headers = Application_Model_kclib_HttpCache::checkMTime($mtime);
	}

	public function localizeAction()
	{

		$request = $this->getRequest();
		$language = $request->getParam('lng','en');
		$this->view->fields = null;
		if( $language != 'en' )
		{
			$translation_dir = realpath(dirname(__FILE__)).'/../models/'.self::DIRECTORY_LANGUAGES;
			//$translation_dir = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.self::DIRECTORY_LANGUAGES;
			// get the correct language
			$filename = $translation_dir.'/'.$language.'.php';
			if( file_exists($filename) )
			{
				require_once $filename;
				$mtime = @filemtime($filename);
				$this->view->headers = Application_Model_kclib_HttpCache::checkMTime($mtime);
				$this->view->language = $language;
				$trclass = new KcTranslation();
				$translation= array();
				foreach ($trclass->lang as $english => $native )
				{
					$translation[Application_Model_kclib_Text::jsValue($english)] =Application_Model_kclib_Text::jsValue($native);
				}
				$this->view->label = $translation;
			}
		}
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


		$kuki['domain'] = 'zfkc.local';//_.kuki.domain = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['cookieDomain']) ? >";
		$kuki['path'] = '/';//_.kuki.path = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['cookiePath']) ? >";
		$kuki['prefix'] = 'MKCFINDER_';//_.kuki.prefix = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['cookiePrefix']) ? >";
		$this->view->type = 'images';
		$this->view->kuki = $kuki;
		$this->view->browser = $browser;
		$this->view->publicPath = $this->_config->kcPath;
		$this->view->label = $this->_getLabels($language);


		$trclass = new KcTranslation();
		$translation= array();
		foreach ($trclass->lang as $english => $native )
		{
			$translation[Application_Model_kclib_Text::jsValue($english)] =Application_Model_kclib_Text::jsValue($native);
		}
		$this->view->label = $translation;
		//$this->view->headScript()->appendFile($this->_config->kcPath.'js/jquery.js','text/javascript');
		//$r = $this->render();
		//$response = $this->getResponse();
		//$response->setBody($r);
		
	}

	public function browseinitAction()
	{

		$request = $this->getRequest();

		$mtime = @filemtime(__FILE__);
		$this->view->headers = Application_Model_kclib_HttpCache::checkMTime($mtime);
		$uploadDir = $this->_uploadDir;
		$typeDir = $this->_uploadDir.self::TYPE;

		$this->view->charset = "utf-8";
		$this->view->data = Application_Model_kcBrowser::act_init($typeDir,$this->getSessionDir());
	}

	public function chdirAction()
	{

		$request = $this->getRequest();
		$dir = $request->getParam('dir','');

		// direct answer, don not render the view
		$this->_helper->viewRenderer->setNoRender();
		$response = $this->getResponse();

		try {
			$directory = Application_Model_kcBrowser::checkDir($this->_uploadDir, $dir);
		} catch (Exception $e){
				
			$message = $e->getMessage();
			$response->appendBody(Zend_Json::encode(array('error' => $message)));
			return ;
		}
		$this->setSessionDir($dir);
		$dirWritable = Application_Model_kclib_Dir::isWritable($directory);
		$files = Application_Model_kcBrowser::getFiles($this->_uploadDir,$dir);
		$answer = array (
			'files' => $files,
			'dirWritable' => $dirWritable			
		);
		$response->appendBody(Zend_Json::encode($answer));

	}

	public function thumbAction(){
		$contextSwitch =$this->_helper->contextSwitch();
		$contextSwitch->initContext();
		
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
		
		$contextSwitch->addContext('tipo', array ('Content-Type'=>'image/png'));
		readfile($thumb);

	}

	public function expandAction(){
		$request = $this->getRequest();
		$dir = $request->getParam('dir','');
		//$this->_helper->json($data, array('enableJsonExprFinder' => true));
		$data = array('dirs' => Application_Model_kcBrowser::getDirs($this->_uploadDir.'/'.$dir));
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


	protected function _getLabels($language)
	{

		if( $language != 'en' )
		{
			$translation_dir = realpath(dirname(__FILE__)).'/../models/'.self::DIRECTORY_LANGUAGES;
			//$translation_dir = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.self::DIRECTORY_LANGUAGES;
			// get the correct language
			$filename = $translation_dir.'/'.$language.'.php';
			if( file_exists($filename) )
			{
				require_once $filename;
				$mtime = @filemtime($filename);
				$this->view->headers = Application_Model_kclib_HttpCache::checkMTime($mtime);
				$this->view->language = $language;
				$trclass = new KcTranslation();
				$translation= array();
				foreach ($trclass->lang as $english => $native )
				{
					$translation[Application_Model_kclib_Text::jsValue($english)] =Application_Model_kclib_Text::jsValue($native);
				}

			}
		}
		return $translation;
	}
}



