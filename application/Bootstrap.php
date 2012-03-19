<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	

	protected function _initSession()
	{
		Zend_Session::start();
		Zend_Layout::startMvc()->setLayout('main');
	}
	
	protected function _initView()
	{
		$this->bootstrap('layout');
		$layout = $this->getResource ( 'layout' );
		$view = $layout->getView();
		$view->doctype('XHTML1_STRICT');
		
		$config = new Zend_Config_Xml ( APPLICATION_PATH . '/configs/navigation.xml', 'nav' );
		$container = new Zend_Navigation ( $config );
		$view->navigation ( $container );
		
		return $view;
	}

	protected function _initRouter() {
		//$router = new My_Controller_Router_CKAdapter();
		$front = Zend_Controller_Front::getInstance();
		$router = $front->getRouter();
		$incPath = get_include_path();
		$kcfinder = new My_KcFinder_Router( 'js/kcfinder', array('controller'=>'Kc'));
		$router->addRoute('js/kcfinder', $kcfinder);

	}
	
	protected function _initHelpers(){
		
	}
	
	

}

