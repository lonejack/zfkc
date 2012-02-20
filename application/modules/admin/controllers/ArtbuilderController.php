<?php

class Admin_ArtBuilderController extends Zend_Controller_Action
{
	protected $role;
	protected $aclHelper;
	protected $auth;
	
	
    public function init()
    {
        /* Initialize action controller here */
    	/* Initialize action controller here */
		$bootstrap = $this->getInvokeArg ( 'bootstrap' );
		$acl = $bootstrap->getResource ( 'acl' );
		$AclHelper = $this->_helper->getHelper ( 'Acl' );
		$AclHelper->generateExOnDenied(true);
		$this->aclHelper = $AclHelper;
		
		$this->auth = My_Auth::getInstance();
		$this->role = $this->auth->getRole ();
		$AclHelper->setAcl ( $acl );
		
		$acl->addResource ( new Zend_Acl_Resource ( 'admin:text' ) );
		$acl->allow ( array('member','admin','superadmin'), 'admin:text' );
		$acl->allow ( array('member','admin','superadmin'), 'admin:text', array ('index', 'listall', 'view', 'create', 'edit', 'delete' ) );
		if( !$acl->isAllowed($this->role,'admin:text' ))
			$this->_redirect ( '/' );
			
		/* Refine Acl access */
		
		
		//$acl->allow ( 'superadmin', 'admin:text', array ('index', 'listall', 'view', 'create', 'edit', 'delete' ) );
    }

    public function indexAction()
    {
        // action body
        $texts = new Admin_Model_ArtBuilderMapper (array('auth'=>$this->auth));
		
		$view = $this->view;
		$view->numtexts = $texts->count ();
    }

    public function viewAction()
    {
        // action body
        $request = $this->getRequest ();
		$id = $request->getParam ( 'id', null );
		
		$text = new Admin_Model_ArtBuilderMapper (array('auth'=>$this->auth));
		$data = $text->find ( $id )->makeArray();
		
		$this->view->data = $data;
    }

    public function editAction()
    {
        // action body
        $request = $this->getRequest ();
		$form = new Admin_Form_ArtBuilder ();
		$helper = $this->getHelper ( 'FormHandler' );
		
		if (!$request->isPost ()) {
			
			$id = $request->getParam ( 'id', null );
			$texts = new Admin_Model_ArtBuilderMapper (array('auth'=>$this->auth));
			$data = $texts->find ( $id )->makeArray();
			$helper->populate ( $form, $data );
			
		} else {
			// Does the POST contain the form namespace? If not, just render the form
			try {
				
				if (! $form->ifValidRemoveHash( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
				$values = $form->getValues(true);
				$values['user_id']= $this->auth->getId();
				
				$texts = new Admin_Model_ArtBuilderMapper (array('auth'=>$this->auth));
				$ret = $texts->update ( $values );
				$this->_redirect ( '/admin/text/list' );
			
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->error = $e->getMessage ();
			}
		}
		$form->addElement ( 'submit', 'create', array ('label' => 'create', 'ignore' => 'true', 'required' => 'false' ) );
		$form->setAction ( '/admin/text/edit#' )->setMethod ( 'post' );
		Zend_Dojo::enableView($this->view);
		$this->view->form = $form;
    }

    public function listAction()
    {
        // action body
        $request = $this->getRequest ();
		$texts = new Admin_Model_ArtBuilderMapper (array('auth'=>$this->auth));
		
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
		
		if (!$request->isPost ()) {
			$id = $request->getParam ( 'id', null );
			$texts = new Admin_Model_ArtBuilderMapper (array('auth'=>$this->auth));
			$text = $texts->find($id);
			
			$form->setDefaults( array('id' => $id,'argument' => $text->argument));
			
		} else {
			// Does the POST contain the form namespace? If not, just render the form
			try {
				
				if (! $form->isValid ( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
				$texts = new Admin_Model_ArtBuilderMapper(array('auth'=>$this->auth));
				$id = $form->getElement ( 'id' )->getValue ();
				$texts->delete($id);
			
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

		$request = $this->getRequest ();
		$form = new Admin_Form_ArtBuilder ();
		$form->removeElement ( 'id' );
		
		$helper = $this->getHelper ( 'FormHandler' );
		
		if ($request->isPost ()) {
			// Does the POST contain the form namespace? If not, just render the form
			try {
				
				if (! $form->ifValidRemoveHash( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
				$values = $form->getValues(true);
				$values['user_id']= $this->auth->getId();
				
				$text = new Admin_Model_ArtBuilderMapper (array('auth'=>$this->auth));
				$ret = $text->create ( $values );
				$this->_redirect ( '/admin/text/list' );
			
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->error = $e->getMessage ();
			}
		}
		$form->addElement ( 'submit', 'create', array ('label' => 'create', 'ignore' => 'true', 'required' => 'false' ) );
		$form->setAction ( '/admin/text/create#' )->setMethod ( 'post' );
		Zend_Dojo::enableView($this->view);
		$this->view->form = $form;
    }
    
    public function selimageAction()
    {
        
        // action body
         $this->_helper->layout->disableLayout();
         $this->view->headScript()->appendFile('/js/jquery/1.6.1/jquery.js');
         $this->view->headScript()->appendFile('/js/galleria/galleria-1.2.3.js');

    }

}











