<?php

class Admin_Model_Text extends My_Model_TableAdmin {
	
	protected $_id;
	protected $owner_id;
	protected $argument;
	protected $part;
	protected $content;
	protected $content_it;
	
	static public function getPrivileges($role)
	{
		$privileges = array(	
			'admin'			=>	array('view','edit','delete'),
			'superadmin' 	=> 	array('view','edit','delete'));
		
		if( isset($privileges[$role]))
			return $privileges[$role];
		else 
			return false;
	}
}

