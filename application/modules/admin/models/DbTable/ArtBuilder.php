<?php

class Admin_Model_DbTable_ArtBuilder extends Zend_Db_Table_Abstract
{

	protected $_name = 'artbuilder';
	protected $_primary = 'id';
	protected $_dependentTables = array('Users');

}

