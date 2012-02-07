<?php

class Admin_Form_ArtBuilder extends Zend_Form
{
	const ANTI_CRSF_KEY = 'anti_csrf_admin_artbuilder';
	
	protected $_default_area;
	
	public function __construct( $default_area = IMAGES_PATH)
	{
		$this->_default_area = $default_area;
		
		parent::__construct();
	}
	
    public function removeAntiCsrf()
    {
    	$this->removeElement(self::ANTI_CRSF_KEY);
    	return ;
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
    	$this->setElementsBelongTo('artbuilder');
    	$this->addElement('hash', self::ANTI_CRSF_KEY, array('salt' => 'unique'));
        $this->addElement('hidden', 'id');
        $this->addElement('text', 'title', array(
            'label'    => 'Titolo: ',
            'required' => true
        ));
        
        $image_0 = new Zend_Form_Element_Select('image_0', array(
            'label'    => 'immagine: ',
            'required' => true,
        ));
        
        $image_mapper = new Admin_Model_ImageMapper();
        $image_mapper->findByPath($this->_default_area);
        $image_0->addMultiOptions(array(
			''				=>	'No Image',
            'TopLeft'		=>	'Top-Left',
			'BottomLeft'	=>	'Bottom-Left',
			'TopRight'		=>	'Top-Right',
			'BottomRight'	=>	'Bottom-Right'        
        ));
        $this->addElement($image_0);

        $pos_image_0 = new Zend_Form_Element_Select('pos_image_0', array(
            'label'    => 'Posizione immagine: ',
            'required' => true,
        ));
        $pos_image_0->addMultiOptions(array(
			''				=>	'No Image',
            'TopLeft'		=>	'Top-Left',
			'BottomLeft'	=>	'Bottom-Left',
			'TopRight'		=>	'Top-Right',
			'BottomRight'	=>	'Bottom-Right'        
        ));
        $this->addElement($pos_image_0);
        
        
        $this->setAttrib('accept-charset', 'utf-8');
    }
}

