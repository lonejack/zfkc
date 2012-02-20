<?php

class UserController extends Zend_Controller_Action
{

    private $_base = null;

    private $_env = null;

    private $_config = null;

    public function init()
    {
        /* Initialize action controller here */
        $uri = $this->_request->getPathInfo ();
        $activeNav = $this->view->navigation ()->findByUri ( $uri );
        $activeNav->active = true;
            	
        $bootstrap = $this->getInvokeArg('bootstrap');
        $config = $bootstrap->getResource('config');
            	
        		
 				
            	
        $this->_base = $this->getFrontController()->getModuleDirectory();
        $this->_env  = $bootstrap->getEnvironment();
        $this->_config = $bootstrap->getResource('config');
            	
            	
            	
    }

    public function loginAction()
    {
        // action body
                
                $handler = $this->_helper->getHelper('HandleLogin');
                $handler->setConfig($this->_config->handlehelper);
                
                if( $handler->hasIdentity() )
                {
                	$helper = $this->getHelper('viewRenderer');
                	$helper->setNoController()->setScriptAction('message');
                	$this->view->message = 'Il Login al server è già stato effettuato';
                	 
                	return;
                }
                
                $request = $this->getRequest();
                $form = new Application_Form_Login();
                $msg = '';
           		if ($request->isPost()) {
                	// Does the POST contain the form namespace? If not, just render the form
           			try {
           				
           				if (!$form->isValid($request->getPost())) 
                	    	throw new Exception('Parametri non corretti');	
                	    	
        	        	if( $handler->doAuth( $form ) ) {
        	        		$dynamicSalt = '';
        	        		for($i=0; $i<25;$i++)
                               $dynamicSalt .= chr(rand(33, 126));
        	        		Zend_Registry::set('csrf_foo', $dynamicSalt);
                        	$this->_redirect($this->_config->handlehelper->onlogin->redirect);
        	        	}
                        throw new Exception('Credenziali non valide');	
                    } catch (Exception $e) {
                        /* rethrow it */
                        $msg = $e->getMessage();
                    }
                }
                $handler->renderLoginForm($form,$msg);
                return;
    }

    public function logoutAction()
    {
        // action body
        $request = $this->getRequest();
    	if ($request->isPost()) {
    		$auth = Zend_Auth::getInstance();
		
			if( $auth->hasIdentity() )
			{
				$auth->clearIdentity();
				$this->_redirect('/');
				return;
			}
    	}
    	else
    	{
    		$form = new Application_Form_Logout();
    		$this->view->form = $form;
    	}
	}


}



