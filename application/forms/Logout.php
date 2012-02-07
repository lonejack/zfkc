<?php

class Application_Form_Logout extends Zend_Form
{

    public function removeAntiCsrf()
    {
    	$this->removeElement('anti_csrf_register');    	
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
        /* Form Elements & Other Definitions Here ... */
    	$this->setElementsBelongTo('logout');
    	$this->addElement('hash', 'anti_csrf_register', array('salt' => 'unique'));
    	$this->addElement('submit', 'logout', array(
            'label'    => 'Logout',
            'ignore'   => 'true',
            'required' => 'false',
        ));
    }


}

