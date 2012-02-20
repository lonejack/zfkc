<?php

class Admin_Model_DbTable_Texts extends Zend_Db_Table_Abstract
{

	protected $_name = 'texts';
	protected $_primary = 'id';
	protected $_dependentTables = array('Users');

}

