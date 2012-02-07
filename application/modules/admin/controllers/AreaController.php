<?php

class Admin_AreaController extends Zend_Controller_Action
{
	protected $role;
	protected $aclHelper;
	protected $auth;
	
	
	public function init() {
		/* Initialize action controller here */
		$bootstrap = $this->getInvokeArg ( 'bootstrap' );
		$acl = $bootstrap->getResource ( 'acl' );
		$AclHelper = $this->_helper->getHelper ( 'Acl' );
		$AclHelper->generateExOnDenied(true);
		$this->aclHelper = $AclHelper;
		
		$this->auth = My_Auth::getInstance();
		$this->role = $this->auth->getRole ();
		$AclHelper->setAcl ( $acl );
		
		if( !$acl->isAllowed($this->role,'module:admin' ))
			$this->_redirect ( '/' );
    }

    public function indexAction()
    {
        // action body
    }

    public function listtownAction()
    {
        // action body
    }

    public function listdistrictsAction()
    {
        // action body
    }

    public function listregionsAction()
    {
        // action body
    }

    public function listnationsAction()
    {
        // action body
    }

    public function ziplistAction()
    {
        // action body
    }


}











