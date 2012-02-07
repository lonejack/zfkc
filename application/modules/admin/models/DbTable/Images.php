<?php

class Admin_Model_DbTable_Images extends Zend_Db_Table_Abstract
{

	protected $_name = 'images';
	protected $_primary = 'id';
	
	protected $_dependentTables = array('Users');

}

