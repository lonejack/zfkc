<?php

class KcController extends Zend_Controller_Action
{
	const	DIRECTORY_LANGUAGES	= 'kcLanguages';		
	protected $_kcfinderDir;
	
	public function init()
	{
		/* Initialize action controller here */
		$this->_kcfinderDir = PUBLIC_PATH.'/js/kcfinder';
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
		$this->view->thumbWidth = 100;
		$this->view->thumbHeight = 100;
	}
	
	public function getjoinerAction()
	{
		$this->view->files = Application_Model_kclib_Dir::content($this->_kcfinderDir."/js/browser", array( 'types' => "file", 'pattern' => '/^.*\.js$/'));
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
		$config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/KcConfig.ini', 'browser' );
		if( isset($config->session) )
		{
			Zend_Session::setOptions($config->session->toArray());
		}
		$this->view->title = 'demo';
		$locale=new Zend_Locale();
		$this->view->language = $locale->getLanguage().'-'.$locale->getRegion();
		if( isset($params['theme']) && file_exists($this->_kcfinderDir."/themes/{$params['theme']}" ) )
		{
			$this->view->theme = $params['theme'];				
		} 
		else
		{
			$this->view->theme = $config->theme;
		}
		
		if( file_exists($this->_kcfinderDir."/themes/{$params['theme']}/init.js" ))
		{
			$this->view->init_theme = $this->_kcfinderDir."/themes/{$params['theme']}/init.js";
		}
		
	}
	

}

