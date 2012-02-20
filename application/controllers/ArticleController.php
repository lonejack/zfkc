<?php
class ArticleController extends Zend_Controller_Action
{
	public function init() {
	
			
	}
	
	public function indexAction(){
		$request = $this->getRequest();
		$id = $request->getParam('id');
		$table = new Application_Model_Articles();
		$row = $table->fetchRow($table->select() ->where('id = ?', $id));
		$this->view->title = $row->title;
		$this->view->content = $row->content;
		$this->view->section_id = $row->section_id;
		$this->view->category_id = $row->cat_id;
				
	}
	
	public function viewAction(){
		$this->_forward('index');
	}
	
	public function createAction(){
		
	}
	
	public function editAction(){
		$request = $this->getRequest();
		$id = $request->getParam('id');
		$table = new Application_Model_Articles();
		$row = $table->fetchRow($table->select() ->where('id = ?', $id));
		$this->view->headScript()->appendFile('/js/ckeditor/ckeditor.js');
		
		$form = new Application_Form_Article();
		$form->populate($row->toArray());
		$this->view->form = $form;
	}
	
	
	
	
}