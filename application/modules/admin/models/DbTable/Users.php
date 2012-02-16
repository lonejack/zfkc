<?php

class Admin_Model_DbTable_Users extends Zend_Db_Table_Abstract
{

	protected $_name = 'users';
	protected $_primary = 'id';
	
	protected $_dependentTables = array('Articles');

}

