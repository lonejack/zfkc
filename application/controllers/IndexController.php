<?php

class IndexController extends Zend_Controller_Action {
	
	public function init() {
		
 		
	}
	
	public function indexAction() {
		
	}
	
	public function aboutAction() {
		// action body
	}
	
	public function sitemapAction() {
		$this->view->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		echo $this->view->navigation ()->sitemap ();
	}
	
	public function contactAction() {
		// action body
	}
	
}









