<?php

class Admin_Form_ImageInsert extends Zend_Form
{
	private $_relative_destination;
	
	public function __construct( $destination = IMAGES_PATH)
	{
		$this->_relative_destination = $destination;
		
		parent::__construct();
	}
	
	public function ifValidRemoveHash($data)
	{
		$ret = $this->isValid($data);
		if($ret)
		{
			$this->removeElement('anti_csrf_imageinsert');
		}
		return $ret;
	}

	public function init()
	{
		/* Form Elements & Other Definitions Here ... */
		// Dojo-enable the form:

		$this->addElement('hash', 'anti_csrf_imageinsert', array('salt' => 'unique'));
		$this->setElementsBelongTo('image');
		$element = new Zend_Form_Element_File('image');
		//$element->setDestination(IMAGES_PATH);
		$element->setDestination($this->_relative_destination)
		        ->setLabel('Upload an image:')
		        ->addValidator('NotExists');
		// ensure only 1 file
		$element->addValidator('Count', false, 1);
		// limit to 100K
		$element->addValidator('Size', false, 80000);
		// only JPEG, PNG, and GIFs
		$element->addValidator('Extension', false, 'jpg,png,gif');
		$this->addElement($element, 'image');
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

