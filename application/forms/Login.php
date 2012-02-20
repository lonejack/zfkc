<?php

class Application_Form_Login extends Zend_Form
{
    public function removeAntiCsrf()
    {
    	$this->removeElement('no_csrf_foo');    	
    }
    
	public function ifValidRemoveHash($data)
    {
    	$ret = $this->isValid($data);
    	if($ret)
    		$this->removeAntiCsrf();
    	return $ret;
    }
	
    public function init()
    {
        $this->setElementsBelongTo('login');
        $this->addElement('hash', 'no_csrf_foo', array('salt' => 'unique'));
        $this->addElement('text', 'username', array(
            'label'    => 'Username: ',
            'required' => true,
        ));
        $this->addElement('password', 'password', array(
            'label'    => 'Password: ',
            'required' => true,
        ));
        $this->addElement('submit', 'login', array(
            'label'    => 'Login',
            'ignore'   => 'true',
            'required' => 'false',
        ));
    }
}
