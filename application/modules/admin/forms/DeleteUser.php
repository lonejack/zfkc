<?php

class Admin_Form_DeleteUser extends Zend_Form
{
    public function removeAntiCsrf()
    {
    	$this->removeElement('anti_csrf_admin');    	
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
    		
        	$this->addElement('text', 'username', array(
        		'label' => 'Sei sicuro di voler eliminare l\'utente '
        	));
        	$this->addElement('hash', 'anti_csrf_admin', array('salt' => 'unique'));
        	$this->addElement('hidden', 'id');
			$this->setElementsBelongTo('delete');
			$this->addElement ( 'submit', 'delete', array ('label' => 'si', 'ignore' => 'true', 'required' => 'false' ) );
			$this->setAttrib('accept-charset', 'utf-8');
			$this->setAction('#')->setMethod('post');
			
    }


}

