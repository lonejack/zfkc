<?php

class Application_Form_Standalone extends Zend_Form
{
	const ANTI_CRSF_KEY = 'anti_csrf_standalone';
	
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
    	$this->setElementsBelongTo('standalone');
        $language = new Zend_Form_Element_Select('language', array(
            'label'    => 'Language: ',
            'required' => true,
        	'onChange' => 'submit()'
        ));
        $language->addMultiOptions(array(
        		//"af"	  =>   "Afrikaans",
        		"pt_BR"   =>   "Brazilian Portuguese",
        		"bg"	  =>   "Bulgarian",
        		"ca"	  =>   "Catalan",
        		"zh_CN"   =>   "Chinese Simplified",
        		"cs"	  =>   "Czech",
        		"da"	  =>   "Danish",
        		"nl"	  =>   "Dutch",
        		"en"	  =>   "English",
        		"fi"	  =>   "Finnish",
        		"fr"	  =>   "French",
        		"de"	  =>   "German",
        		"el"	  =>   "Greek",
        		"hu"	  =>   "Hungarian",
        		"it"	  =>   "Italian",
        		"ja"	  =>   "Japanese",
        		"lt"	  =>   "Lithuanian",
        		"no"	  =>   "Norwegian",
        		"fa"	  =>   "Persian",
        		"pl"	  =>   "Polish",
        		"pt"	  =>   "Portuguese",
        		"ro"	  =>   "Romainan",
        		"ru"	  =>   "Russian",
        		"sk"	  =>   "Slovak",
        		"es"	  =>   "Spanish",
        		"sv"	  =>   "Swedish",
        		"tr"	  =>   "Turkish",
        		"uk"	  =>   "Ukrainian",
        		"vi"	  =>   "Vietnamese"));
        $this->addElement($language);
        $style = new Zend_Form_Element_Select('style', array(
        		'label'    => 'Style: ',
        		'required' => true,
        		'onChange' => 'submit()'
        ));
        $style->addMultiOptions(array(
        		"dark"	  		=>   "dark",
        		"oxygen"	  =>   "oxygen"));
        $this->addElement($style);
        
        $directory = new Zend_Form_Element_Select('directory', array(
        		'label'    => 'Directory: ',
        		'required' => true,
        		'onChange' => 'submit()'
        ));
        $directory->addMultiOptions(array(
        		"images"	=>   "images",
        		"flash"		=>   "flash",
        		"files"	  	=>   "files"));
        $this->addElement($directory);
        
    	$this->addElement('hash', self::ANTI_CRSF_KEY, array('salt' => 'unique'));
    	$this->addElement('hidden', 'id');
        $this->setAttrib('accept-charset', 'utf-8');
    }
}

