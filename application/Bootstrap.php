<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected $_view;
	
	protected function _initSession()
	{
		Zend_Session::start();
	}
	protected function _initDoctype()
	{
		$this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('XHTML1_STRICT');
		$this->_view = $view;
	}
	
	protected function _initRouter() {
			//$router = new My_Controller_Router_CKAdapter();
			$front = Zend_Controller_Front::getInstance();
			$router = $front->getRouter();
			$incPath = get_include_path();
			$kcfinder = new My_KcFinder_Router( 'js/kcfinder', array('controller'=>'Kc'));
			$router->addRoute('js/kcfinder', $kcfinder);			

		}
	
}

