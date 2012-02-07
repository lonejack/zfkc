<?php

class Admin_Model_ImageMapper extends My_Model_AdminMapper
{
	protected $_dbTable;
	protected $_options;
	protected $_permission;
	
	public function __construct(array $options = null)
	{
		$this->_options = $options;
		$this->_permission = true;
		$options = array(
			'table'		=> 'Admin_Model_DbTable_Images',
			'rowClass'	=> 'Admin_Model_Image'
		);
		parent::__construct($options);
	}

	public function delete($id) {
		$text = $this->find($id);
		
		$users = new Admin_Model_UserMapper();
		$user = $users->find($text->user_id);
		
		$auth = $this->_options['auth'];
		$role = $auth->getRole();
		if( ($role == 'superadmin' && $user->role == 'superadmin' && $user->id != $auth->getId())|
		    ($role == 'admin' && $user->role == 'admin' && $user->id != $auth->getId())|
		    ($role == 'admin' && $user->role == 'superadmin' )|
		    ($role != 'superadmin' && $role != 'admin' && $user->id != $auth->getId() ))
			throw new Zend_Exception("Non ti è consentito eliminare un articolo di $user->username!"); 
		
		$data = $text->makeArray();
		$data['state'] = 0;
		return $this->getDbTable()->update($data, "id= ".$data['id']);
	}
	
	public function update( $new_data)
	{
			
		$id = $new_data['id'];
		$old_data = $this->getDbTable()->find($id)->current();
		if($old_data['user_id'] != $new_data['user_id'])
		{
			throw new Zend_Exception('Non sei proprietario dell\'immagine, non puoi cambiarla'); 
		}
		
		unset($new_data['id']);
		$new = $this->getDbTable()->update($new_data, "id = '$id'");
	}	
	
	public function getItems($offset, $itemCountPerPage)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$select = new Zend_Db_Select($db);
		$select->from(array('a'=>'images'))
		       ->joinLeft(array('u'=>'users'),'a.user_id = u.id',array('username'))->where('a.state=1')
		       ->limit($itemCountPerPage,$offset);
		
				
		$stmt = $db->query($select);
		$result = $stmt->fetchAll();
		
		return $this->_makeArrayObjects($result);		
	}
	
	public function findByPath($path) {
		$db = Zend_Db_Table::getDefaultAdapter();
		$select = new Zend_Db_Select($db);
		$select->from(array('a'=>'images'))->where("a.path='$path'");
		$stmt = $db->query($select);
		$rows = $stmt->fetchAll();
		$entry = array();
		foreach ($rows as $row )		
			$entry[] = new $this->_rowClass($row);
		
		return $entry;
	}
	
	public function findData($id) {
		$db = Zend_Db_Table::getDefaultAdapter();
		$select = new Zend_Db_Select($db);
		$select->from(array('a'=>'images'))
		       ->joinLeft(array('u'=>'users'),'a.user_id = u.id',array('username'))->where('a.state=1')
		       ->where('a.id = ?',$id);
		$stmt = $db->query($select);
		
		$row = $stmt->fetch();
		
		
		$entry = new $this->_rowClass($row);
		return $entry;
	}
	
	public function isAllowed()
	{
		return $this->_permission;	
	}
}

