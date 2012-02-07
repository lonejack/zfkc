<?php

class Admin_Model_Image extends My_Model_TableAdmin {
	
	protected $_id;
	protected $_user_id;
	protected $name;
	protected $_path;
	protected $_thumbnail;
	protected $content;
	protected $content_it;
	
	
	static public function getPrivileges($role)
	{
		$privileges = array(	
			'admin'			=>	array('view','edit','delete','thumbnail'),
		    'member'		=>	array('view','edit','delete','thumbnail'),
			'superadmin' 	=> 	array('view','edit','delete','thumbnail'));
		
		if( isset($privileges[$role]))
			return $privileges[$role];
		else 
			return false;
	}
	
}

