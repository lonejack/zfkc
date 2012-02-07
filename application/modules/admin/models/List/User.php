<?php
class Admin_Model_List_User
{
	public function __construct($role_access,$id)
	{
		$pf = array( 'listall','delete','view','edit');
//		$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
//		$db= $bootstrap->getResource('db');
		$db = Zend_Db_Table::getDefaultAdapter();
		$select = $db->select()
			->from(array('u' => 'users'),'*')
			->join(array('a' => 'acl_record_by_role'), 'u.id_role = a.owner_role',$pf)
			->where('listall = 1')->where('a.tabname = ?','user')
			->where('a.access_role = ?', $role_access)->order('username ASC');
		$qry = $db->query($select);
		$results = $qry->fetchAll(Zend_Db::FETCH_OBJ);
		$str= $select->__toString();
		$users = new Admin_Model_DbTable_Users();
		
				
	}
	
	public function ListAll($role_access, $offset, $itemCountPerPage)
	{
		
		$users = new Admin_Model_DbTable_Users();
		
		
	
	}
	
}
