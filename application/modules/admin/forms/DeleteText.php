<?php

class Admin_Form_DeleteText extends Zend_Form
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
    		
        $this->addElement('text', 'argument', array(
        	'label' => 'Sei sicuro di voler eliminare l\'articolo '
        ));
        $this->addElement('hidden', 'id');
        //$rr = new Zend_Form_Element_Hash('pippo');
        $this->addElement('hash', 'anti_csrf_admin', array('salt' => 'unique'));
		$this->setElementsBelongTo('delete');
		$this->addElement ( 'submit', 'delete', array ('label' => 'si', 'ignore' => 'true', 'required' => 'false' ) );
		$this->setAttrib('accept-charset', 'utf-8');
		$this->setAction('#')->setMethod('post');
			
    }


}

