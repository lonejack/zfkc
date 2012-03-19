<?php
class DemoController extends Zend_Controller_Action
{
	public function init() {
	
			
	}
	
	public function standaloneAction(){
		
	}

	public function indexAction(){
	
	}
	
	
	public function ckeditorAction(){
		$this->view->headScript()->appendFile('/js/ckeditor/ckeditor.js');
		
	}
	
}