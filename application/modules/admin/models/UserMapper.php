<?php

class Admin_Model_UserMapper extends My_Model_AdminMapper
{
	protected $_dbTable;
	protected $_options;
	protected $_permission;
	
	public function __construct(array $options = null)
	{
		$this->_options = $options;
		$this->_permission = true;
		//$R = new Admin_Model_List_User(2,1);
		$options = array(
					'table'		=> 'Admin_Model_DbTable_Users',
					'rowClass'	=> 'Admin_Model_User'
		);
		parent::__construct($options);
	}

	public function create($data) {
		unset($data['id']);
			
		return $this->getDbTable()->insert($data);
	}

	public function find($id) {
		$row = $this->getDbTable()->find($id)->current();
		$entry = new Admin_Model_User($row);
		return $entry;
	}
	
	public function delete($id) {
		$user = $this->find($id);
		$auth = $this->_options['auth'];
		$role = $auth->getRole();
		if( ($role != 'superadmin' && $role != 'admin' ) ||
		  ($role == 'admin' && in_array($user->role, array('superadmin','admin') ) ) )
			throw new Zend_Exception("Non ti è consentito eliminare $user->username!"); 
		
		$data = $user->makeArray();
		$data['state'] = 0;
		return $this->getDbTable()->update($data, "id= ".$data['id']);
	}
	
	public function update( $new_data)
	{
			
		$id = $new_data['id'];
		$old_data = $this->getDbTable()->find($id)->current();
		if($old_data['role'] != $new_data['role'])
		{
			$auth = $this->_options['auth'];
			$role = $auth->getRole();
			$new_role = $new_data['role'];
			if($role == 'admin' && ($new_role=='admin' || $new_role=='superadmin'))
				throw new Zend_Exception('Non è possibile cambiare il ruolo'); 
		}
		
		unset($new_data['id']);
		$new = $this->getDbTable()->update($new_data, "id = '$id'");
	}	
	
	protected function _makeArrayObjects($setArray,$allowed=null)
	{
		$entries = array();
		foreach ($setArray as $row) {
			$entry = new Admin_Model_User($row,$allowed=null);
			$entries[] = $entry;
		}
		return $entries;
	}

	public function fetchAll($allowed=null) {
		$resultSet = $this->getDbTable()->fetchAll('state=1'); //Zend_Db::FETCH_ASSOC
		return $this->_makeArrayObjects($resultSet,$allowed=null);
	}
	
	public function getItems($offset, $itemCountPerPage)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$select = new Zend_Db_Select($db);
		$select->from(array('a'=>'users'))->limit($itemCountPerPage,$offset);
		
				
		$stmt = $db->query($select);
		$result = $stmt->fetchAll();
		
		return $this->_makeArrayObjects($result);		
	}
	
	public function count () 
	{
		$table = $this->getDbTable();
		$select = $table->select();
		$select->from($table, 'COUNT(*) as count');
		return $table->fetchRow($select)->count;
	
	}
	
	public function isAllowed()
	{
		return $this->_permission;
	}
}

