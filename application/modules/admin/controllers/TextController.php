<?php

class Admin_TextController extends Zend_Controller_Action
{
	protected $role;
	protected $aclHelper;
	protected $auth;
	
	
    public function init()
    {
        /* Initialize action controller here */
		
		$auth= Zend_Auth::getInstance();
		$user= $auth->getStorage()->read();
		$this->role = $user->role;
		$this->_user_id = $user->id;
		$this->_credential = array('user_id'=>$user->id ); 
		$this->auth = Zend_Auth::getInstance();
    }

    public function indexAction()
    {
        // action body
    	$table = new Zend_Db_Table(ARTICLES_TABLE);
    	$select = $table->select();
    	$select->from($table,array('COUNT(*) as count'))->where('1');
    	$data = $table->fetchAll($select)->current();
		$this->view->numtexts = $data['count']; 
    }

    public function viewAction()
    {
        // action body
        $request = $this->getRequest ();
		$id = $request->getParam ( 'id', null );
		
		$text = new Admin_Model_TextMapper (array('auth'=>$this->auth));
		$data = $text->find ( $id )->makeArray();
		
		$this->view->data = $data;
    }

    public function editAction()
    {
        // action body
    	$this->view->headScript()->appendFile($this->view->baseUrl().'/js/ckeditor/ckeditor.js');
    	
    	$form = new Admin_Form_Text ();
		
		$table = new Zend_Db_Table(ARTICLES_TABLE);
		$request = $this->getRequest ();
		
    			
		if (!$request->isPost ()) {
			$id = $request->getParam ( 'id', null );
			$select = $table->select();
			$select->where('id = ?',$id);
			$lista = $table->fetchAll($select);
			if( !$lista->count() )
			{
				throw new Zend_Exception('Richiesta non valida!!');
			}
			$dati = $lista->current()->toArray();
			$form->populate($dati);
			
		} else {
			try {
				if (! $form->ifValidRemoveHash ( $request->getPost () ))
				throw new Exception ( 'Parametri non corretti' );
				$data = $form->getValues(true);
				$select = $table->select();
				$select->where('id = ?',$data['id']);
				$lista = $table->fetchAll($select);
				if( !$lista->count() )
				{
					throw new Zend_Exception('Record non presente!');
				}
				$where = $table->getAdapter()->quoteInto('id = ?', $data['id']);
				$table->update($data, $where);
				$this->_redirect ( '/admin/text/list' );
			
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->error = $e->getMessage ();
			}
		}
		$form->addElement ( 'submit', 'create', array ('label' => 'create', 'ignore' => 'true', 'required' => 'false' ) );
		$form->setAction ( '/admin/text/edit#' )->setMethod ( 'post' );
		
		$this->view->form = $form;
    }

    public function listAction()
    {
        // action body
        $request = $this->getRequest ();
		$texts = new Admin_Model_TextMapper (array('auth'=>$this->auth));
		
		$paginator = new Zend_Paginator ( $texts );
		$page = $request->getParam ( 'page', null );
		$paginator->setCurrentPageNumber ( $page );
		
		$this->view->entries = $paginator->getCurrentItems ();
		Zend_Paginator::setDefaultScrollingStyle ( 'Sliding' );
		Zend_View_Helper_PaginationControl::setDefaultViewPartial ( '_pagination_control.phtml' );
		
		$helper = $this->getHelper ( 'ListHelper' );
		$privileges = Admin_Model_Text::getPrivileges ( $this->role );
		$helper->setPrivileges ( $this->view->entries, $privileges );
		
		$paginator->setView ( $this->view );
		$this->view->paginator = $paginator;
    }

    public function deleteAction()
    {
        // action body
		$request = $this->getRequest ();
		$form = new Admin_Form_DeleteText();
		$table = new Zend_Db_Table(ARTICLES_TABLE);
		
		if (!$request->isPost ()) {
			$id = $request->getParam ( 'id', null );
			$select = $table->select();
			$select->where('id = ?',$id);
			$lista = $table->fetchAll($select);
			if( !$lista->count() )
			{
				throw new Zend_Exception('Richiesta non valida!!');
			}
			$text = $lista->current();
			$form->setDefaults( array('id' => $text->id,'argument' => $text->argument));
			
		} else {
			// Does the POST contain the form namespace? If not, just render the form
			try {
				
				if (! $form->isValid ( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
				$id = $form->getElement ( 'id' )->getValue ();
				$select = $table->select();
				$select->where('id = ?',$id);
				$lista = $table->fetchAll($select);
				if( !$lista->count() )
				{
					throw new Zend_Exception('Richiesta non valida!!');
				}
				$where = $table->getAdapter()->quoteInto('id = ?', $id);
				$table->delete($where);
			
				$this->_redirect ( '/admin/text/list' );
			
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->placeholder('error')->set($e->getMessage ());
				
			}
		
		}
		$this->view->form = $form;
    }

    public function createAction()
    {
        // action body
        // action body
    	$this->view->headScript()->appendFile($this->view->baseUrl().'/js/ckeditor/ckeditor.js');

		$request = $this->getRequest ();
		$form = new Admin_Form_Text ();
		$form->removeElement ( 'id' );
		$table = new Zend_Db_Table(ARTICLES_TABLE);
		
		if ($request->isPost ()) {
			try {
				
				if (! $form->ifValidRemoveHash( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
				$values = $form->getValues(true);
				$values['owner_id']= $this->_user_id;
				
				$ret = $table->insert( $values );
				$this->_redirect ( '/admin/text/list' );
			
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->error = $e->getMessage ();
			}
		}
		$form->addElement ( 'submit', 'create', array ('label' => 'create', 'ignore' => 'true', 'required' => 'false' ) );
		$form->setAction ( '/admin/text/create#' )->setMethod ( 'post' );
		//Zend_Dojo::enableView($this->view);
		$this->view->form = $form;
    }


}











