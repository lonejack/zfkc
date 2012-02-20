<?php

class Admin_Model_User extends My_Model_TableAdmin {
	
	protected $_id;
	protected $_username;
	protected $_role;
	protected $_state;
	protected $email;
	protected $first_name;
	protected $last_name;
	protected $url;
	protected $address;
	protected $province;
	protected $zip;
	protected $_download_area;
	
	
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

