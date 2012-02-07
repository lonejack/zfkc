<?php

class Admin_Form_Text extends Zend_Form
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
    	$this->addElement('hidden', 'id', array(
            'label'    => 'id: ',
            'required' => true,
        ));
        // Dojo-enable the form:
        //Zend_Dojo::enableForm($this);
        $this->addElement('hash', 'anti_csrf_admin', array('salt' => 'unique'));
        $this->setElementsBelongTo('article');
        $this->addElement('text', 'argument', array(
            'label'    	=> 'title: ',
            'size'		=> '50',
            'required' 	=> true
        ));
        $this->addElement('text', 'part', array(
            'label'    => 'Tags: ',
            'size'		=> '50',
        	'required' => true
        ));
        $this->addElement('textarea', 'content', array(
            'label'    => 'Scrivi articolo: ',
            'id'		=> 'content',
            'name'		=> 	'content'
		));
		$this->setAttrib('accept-charset', 'utf-8');
    }
}

