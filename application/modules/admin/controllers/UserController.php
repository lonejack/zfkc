<?php

class Admin_UserController extends Zend_Controller_Action {
	
	protected $role;
	protected $aclHelper;
	protected $auth;
	protected $_dbAdapter;
	protected $_role_id;
	protected $_user_id;
	protected $_credential;
	
	/**
	 * the controller configuration and action for user 
	 */
	protected $_config;
	
	public function init() {
		/* Initialize action controller here */
		/* get the acl */
		$bootstrap = $this->getInvokeArg ( 'bootstrap' );
		$acl = $bootstrap->getResource ( 'acl' );
		$AclHelper = $this->_helper->getHelper ( 'Acl' );
		$AclHelper->generateExOnDenied(true);
		$this->aclHelper = $AclHelper;
		
		$this->auth = Zend_Auth::getInstance();
		
		$AclHelper->setAcl ( $acl );
		
		$auth= Zend_Auth::getInstance();
		$user= $auth->getStorage()->read();
		$this->role = $user->role;
		//$this->_role_id = $user->id_role;
		$this->_user_id = $user->id;
		$this->_credential = array('user_id'=>$user->id ); 

		/*
		if( !$acl->isAllowed($this->role,'module:admin' ))
			$this->_redirect ( '/' );
		*/
		$action = $this->getRequest()->getActionName();
		$role = $this->role;
		$this->_config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/Admin_UserController.ini', $role .'_'. $action );
		$table = $this->_config->tables;
		$user=$this->_config->user;
		//$fet = $this->_config->table;
		//$users = $fet->users;
		$resource = $bootstrap->getPluginResource('db');
		$this->_adapter = $resource->getDbAdapter();
	
		
		/* Refine Acl access */
		$acl->addResource ( new Zend_Acl_Resource ( 'admin:user' ) );
		My_Acl::ConfigResource($acl,'admin:user',$this->role,  $this->_config->acl );
	}
	
	public function indexAction() {
		// action body
		
		$users = new Admin_Model_UserMapper (array('auth'=>$this->auth));
		
		$view = $this->view;
		$view->numUsers = $users->count ();
	}
	
	public function listallAction() {
		
		$request = $this->getRequest ();
		$users = new Admin_Model_UserMapper (array('auth'=>$this->auth));
		
		$paginator = new Zend_Paginator ( $users );
		$page = $request->getParam ( 'page', null );
		$paginator->setCurrentPageNumber ( $page );
		
		$this->view->entries = $paginator->getCurrentItems ();
		Zend_Paginator::setDefaultScrollingStyle ( 'Sliding' );
		Zend_View_Helper_PaginationControl::setDefaultViewPartial ( '_pagination_control.phtml' );
		
		$helper = $this->getHelper ( 'ListHelper' );
		$privileges = Admin_Model_User::getPrivileges ( $this->role );
		$helper->setPrivileges ( $this->view->entries, $privileges );
		
		$paginator->setView ( $this->view );
		$this->view->paginator = $paginator;
	}
	
	public function createAction() {
		// action body

		$request = $this->getRequest ();
		$form = new Admin_Form_User ();
		$form->removeElement ( 'id' );
		$form->removeElement ( 'role' );
		
		$helper = $this->getHelper ( 'FormHandler' );
		
		if ($request->isPost ()) {
			// Does the POST contain the form namespace? If not, just render the form
			try {
				
				if (! $form->ifValidRemoveHash ( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
				$data = $form->getValues(true);
				$table = new Zend_Db_Table(USERS_TABLE);
				$table->insert($data);
				$this->_redirect ( '/admin/user/listall' );
			
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->error = $e->getMessage ();
			}
		}
		$form->addElement ( 'submit', 'create', array ('label' => 'create', 'ignore' => 'true', 'required' => 'false' ) );
		$form->setAction ( '/admin/user/create#' )->setMethod ( 'post' );
		$this->view->form = $form;
	}
	
	/**
	 * 
	 */
	public function viewAction() {
		
		$request = $this->getRequest ();
		$id = $request->getParam ( 'id', null );
		
		$table = new Zend_Db_Table(USERS_TABLE);
		
		$select = $table->select();
		$select->where('id = ?',$id);
		$lista = $table->fetchAll($select);
		if( !$lista->count() )
		{
			throw new Zend_Exception('Richiesta non valida!!');
		}
		
		$this->view->data = $lista->current()->toArray();
	}
	
	/*
	 * 
	 */
	public function editAction() {
		// action body
		$form = new Admin_Form_User ();
		
		$table = new Zend_Db_Table(USERS_TABLE);
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
				$this->_redirect ( '/admin/user/listall' );
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->placeholder('error')->set($e->getMessage ());
			}
		}
		
		$form->setAction ( '/admin/user/edit#' )->setMethod ( 'post' );
		$form->addElement ( 'submit', 'update', array ('label' => 'update', 'ignore' => 'true', 'required' => 'false' ) );
		$this->view->form = $form;
	}
	
	public function deleteAction() {
		// action body
		$request = $this->getRequest ();
		$form = new Admin_Form_DeleteUser();
		$table = new Zend_Db_Table(USERS_TABLE);
		
		if (!$request->isPost ()) {
			$id = $request->getParam ( 'id', null );
			$select = $table->select();
			$select->where('id = ?',$id);
			$lista = $table->fetchAll($select);
			if( !$lista->count() )
			{
				throw new Zend_Exception('Richiesta non valida!!');
			}
			$user = $lista->current();
			$form->setDefaults( array('id' => $user->id,'username' => $user->username));
			
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
			
				$this->_redirect ( '/admin/user/listall' );
			
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->placeholder('error')->set($e->getMessage ());
				
			}
		
		}
		$this->view->form = $form;
		
	}
	
	public function editmyAction()
	{
		
	}
	
	public function viewmyAction()
	{
	
	}
	

}















