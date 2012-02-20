<?php

class Admin_Form_ImageDetail extends Zend_Form
{
    public function ifValidRemoveHash($data)
    {
    	$ret = $this->isValid($data);
    	if($ret)
    		$this->removeElement('anti_csrf_imagedetail');    	
    	return $ret;
    }
    
    public function init()
    {
       $this->addElement('hidden', 'id');
		

		$this->addElement('hash', 'anti_csrf_imagedetail', array('salt' => 'unique'));
		$this->setElementsBelongTo('imagedetail');
		$this->addElement('text', 'name', array(
            'label'    => 'nome immagine: ',
            'required' => true
        ));
		$this->addElement('text', 'description', array(
            'label'    => 'descrizione: ',
            'required' => true
        ));
        $this->setAttrib('enctype', 'multipart/form-data');
		$this->setAttrib('accept-charset', 'utf-8');
    }
}

