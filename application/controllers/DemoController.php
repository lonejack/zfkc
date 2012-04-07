<?php
class DemoController extends Zend_Controller_Action
{
	public function init() {
	
			
	}
	
	public function standaloneAction(){
		$form = new Application_Form_Standalone();
		$form->setAction('/demo/standalone')
		->setMethod('post');
		$this->view->form =$form;
		$this->view->headLink()->appendStylesheet('/styles/zend_form.css', $attributes = array());
		
		$sa_config = new Zend_Session_Namespace('sa_config');
		if( !isset($sa_config->language) ) {
			$locale=new Zend_Locale();
			$sa_config->language = $locale->getLanguage();
		}
		if( !isset($sa_config->style) ) {
			$sa_config->style = 'oxygen';
		}
		if( !isset($sa_config->directory) ) {
			$sa_config->directory = 'images';
		}
		
		$request = $this->getRequest();
		
		
		if($request->isPost()) {
			try {
				if (! $form->ifValidRemoveHash ( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
				
				$sa_config->language = $form->getValue('language');
				$sa_config->style = $form->getValue('style');
				$sa_config->directory = $form->getValue('directory');
				
				
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->error = $e->getMessage ();
			}
			$this->_redirect('/demo/standalone');
		}
		else {
			$form->populate(
				array(	'language'	=>	$sa_config->language, 
						'style'		=>	$sa_config->style,
						'directory'	=>	$sa_config->directory ));
			
		}
		$this->view->language 	= $sa_config->language;
		$this->view->style 		= $sa_config->style;
		$this->view->directory 	= $sa_config->directory;
		if( $sa_config->style == 'dark')
			$this->view->ifbackground = '#3B4148';
		else
			$this->view->ifbackground = '#e0dfde';
	}

	public function indexAction(){
	
	}
	
	
	public function ckeditorAction(){
		$this->view->headScript()->appendFile('/js/ckeditor/ckeditor.js');
		
	}
	
}