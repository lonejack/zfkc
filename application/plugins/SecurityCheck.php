<?php
/**
 * App_Plugin_SecurityCheck
 * 
 * @author Enrico Zimuel (enrico@zend.com)
 */
class Application_Plugin_SecurityCheck extends Zend_Controller_Plugin_Abstract
{
	const NO_AUTH_MODULE='login';
	const DEFAULT_MODULE='default';
	private $_controller;
	private $_module;
	private $_action;
	private $_role;
	
	public function preDispatch (Zend_Controller_Request_Abstract $request)
	{
		$this->_controller = $this->getRequest()->getControllerName();
		$this->_module= $this->getRequest()->getModuleName();
		$this->_action= $this->getRequest()->getActionName();
		
		$auth= Zend_Auth::getInstance();
		$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$acl= $bootstrap->getResource('acl');
		$redirect=true;
		if ($this->_isAuth($auth)) {
			$user= $auth->getStorage()->read();
			$role= $user->role;
		}
		else 
		{
			$role= 'guest';
		}
		if ($this->_checkAllowed($role,$acl)) {
			$redirect=false;
		}
		
		if ($redirect) {
			$request->setModuleName('');
			$request->setControllerName('index');
			$request->setActionName('index');
        }
		
	}
    /**
     * preDispatch1
     * 
     * @param Zend_Controller_Request_Abstract $request
     */
	public function preDispatch1 (Zend_Controller_Request_Abstract $request)
	{
		
		$this->_controller = $this->getRequest()->getControllerName();
		$this->_module= $this->getRequest()->getModuleName();
		$this->_action= $this->getRequest()->getActionName();

		$auth= Zend_Auth::getInstance();
		
		$force_reload = false;

		$redirect=true;
		if ($this->_module != self::NO_AUTH_MODULE && $this->_module != self::DEFAULT_MODULE ) {
			if ($this->_isAuth($auth)) {
				$user= $auth->getStorage()->read();
				$this->_role= $user->role_id;
				$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
				$db= $bootstrap->getResource('db');

				$manager = $bootstrap->getResource('cachemanager');
				$cache = $manager->getCache('acl');
				if( $force_reload )
				{
					$acl= new My_Login_Acl($db,$this->_role);
					$cache->save($acl,'ACL_'.$this->_role);
				}
				elseif (($acl= $cache->load('ACL_'.$this->_role))===false || $force_reload) {
					$acl= new My_Login_Acl($db,$this->_role);
					$cache->save($acl,'ACL_'.$this->_role);
				}

				if ($this->_isAllowed($auth,$acl)) {
					$redirect=false;
				}
			}
		} else {
			$redirect=false;
		}

		if ($redirect) {
			$request->setModuleName('');
			$request->setControllerName('index');
			$request->setActionName('index');
        }
    }
    /**
     * Check user identity using Zend_Auth
     * 
     * @param Zend_Auth $auth
     * @return boolean
     */
    private function _isAuth (Zend_Auth $auth)
    {
    	if (!empty($auth) && ($auth instanceof Zend_Auth)) {
        	return $auth->hasIdentity();
    	} 
    	return false;	
    }
    /**
     * Check permission using Zend_Auth and Zend_Acl
     * 
     * @param Zend_Auth $auth
     * @param Zend_Acl $acl
     * @return boolean
     */
    private function _isAllowed(Zend_Auth $auth, Zend_Acl $acl) 
    {
    	if (empty($auth) || empty($acl) ||
    		!($auth instanceof Zend_Auth) ||
    		 !($acl instanceof Zend_Acl)) {
    			return false;
    	}
    	$resources= array (
    		'*/*/*',
    		$this->_module.'/*/*', 
    		$this->_module.'/'.$this->_controller.'/*', 
    		$this->_module.'/'.$this->_controller.'/'.$this->_action
    	);
    	$result=false;
    	foreach ($resources as $res) {
    		if ($acl->has($res)) { 
    			$result= $acl->isAllowed($this->_role,$res);
    		}
    	}
    	return $result;
    }
	/**
     * Check permission using Zend_Auth and Zend_Acl
     * 
     * @param Zend_Auth $auth
     * @param Zend_Acl $acl
     * @return boolean
     */
    private function _checkAllowed($role, Zend_Acl $acl) 
    {
    	if (empty($acl) || !($acl instanceof Zend_Acl)) {
    			return false;
    	}
    	$resources= array (
    		'*/*/*',
    		$this->_module.'/*/*', 
    		$this->_module.'/'.$this->_controller.'/*', 
    		$this->_module.'/'.$this->_controller.'/'.$this->_action
    	);
    	$result=false;
    	foreach ($resources as $res) {
    		if ($acl->has($res)) { 
    			$result= $acl->isAllowed($role,$res);
    		}
    	}
    	return $result;
    }
}
