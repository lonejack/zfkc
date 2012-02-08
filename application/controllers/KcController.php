<?php

class KcController extends Zend_Controller_Action
{
	const	DIRECTORY_LANGUAGES	= 'kcLanguages';		
	protected $_kcfinderDir;
	protected $_realpath;
	protected $_uploadDir;
	protected $_uploadUrl;
	
	public function init()
	{
		/* Initialize action controller here */
		$this->_config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/KcConfig.ini', 'browser' );
		$this->_realpath = PUBLIC_PATH.'/'.$this->_config->publicPath;
		$this->_uploadDir = PUBLIC_PATH;
		$this->_uploadUrl = $this->_config->imagesDir;
		
		
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
		$this->view->files = Application_Model_kclib_Dir::content($this->_realpath."/js/browser", array( 'types' => "file", 'pattern' => '/^.*\.js$/'));
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
			$translation_dir = realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.self::DIRECTORY_LANGUAGES;
			//$translation_dir = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.self::DIRECTORY_LANGUAGES;
			// get the correct language
			$filename = $translation_dir.DIRECTORY_SEPARATOR.$language.'.php';
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
					$translation[Application_Model_kclib_Text::jsValue($english)] =Application_Model_kclib_Text::jsValue($native)	;				
				}
				$this->view->translation = $translation;
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
		$this->view->language = $locale->getLanguage();
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
		$browser['access'] = json_encode($this->_config->access);
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
		$browser['access'] = '{"files":{"upload":true,"delete":true,"copy":true,"move":true,"rename":true},"dirs":{"create":true,"delete":true,"rename":true}}';
		$front = Zend_Controller_Front::getInstance();
		
		
		$kuki['domain'] = 'zfkc.local';//_.kuki.domain = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['cookieDomain']) ? >";
		$kuki['path'] = '/';//_.kuki.path = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['cookiePath']) ? >";
		$kuki['prefix'] = 'KCFINDER_';//_.kuki.prefix = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['cookiePrefix']) ? >";
		$this->view->type = 'images';
		$this->view->kuki = $kuki;
		$this->view->browser = $browser;
		$this->view->publicPath = 'http://zfkc.local/'.$this->_config->publicPath;
		
	}
	
	public function browseinitAction()
	{
		$request = $this->getRequest();
		$type=$request->getParam('images',$this->_config->imagesDir);
		$mtime = @filemtime($filename);
		$this->view->headers = Application_Model_kclib_HttpCache::checkMTime($mtime);
		$uploadDir = $this->_uploadDir.$this->_config->uploadURL;
		$typeDir = $this->_uploadDir.$this->_config->uploadURL.$type;
		
		
		$this->view->data = Application_Model_kcBrowser::act_init($typeDir,$this->getSessionDir(), $uploadDir);
	}
	
	public function browsechdirAction()
	{
		$request = $this->getRequest();
		$dir = $request->getParam('dir', trim($this->_config->imagesDir,'/'));
		$this->setSessionDir($dir);		
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
		$zf_kceditor->sessionDir = dir;
	}

}

