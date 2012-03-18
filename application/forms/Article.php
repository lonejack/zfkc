<?php

class Application_Form_Article extends Zend_Form
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
        $this->addElement('text', 'title', array(
            'label'    => 'Titolo: ',
            'required' => true
        ));
        $this->addElement('textarea', 'content', array(
            'label'    => 'Contenuto',
            'required' => true
        ));
    	$this->addElement('hash', self::ANTI_CRSF_KEY, array('salt' => 'unique'));
    	$this->addElement('hidden', 'id');
        $this->setAttrib('accept-charset', 'utf-8');
    }
}

