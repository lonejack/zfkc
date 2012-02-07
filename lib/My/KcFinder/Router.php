<?php
class My_KcFinder_Router extends Zend_Controller_Router_Route_Static
{
	protected $_defaults = array();
	public function match($path, $partial = false)
	{
		$path = trim($path, self::URI_DELIMITER);
		if( strncmp ( $path, $this->_route, strlen($this->_route)) != 0 )
		{
			return false;
		}
		$path = trim(substr($path, strlen($this->_route)), self::URI_DELIMITER);
		$pathex = explode(self::URI_DELIMITER, $path);
		$front = Zend_Controller_Front::getInstance();
		$request = $front->getRequest();
		$defaults = $this->getDefaults();
		$params = $request->getParams();
		switch($pathex[0])
		{
			case 'css.php':
				if($params['type'] == 'images')
				{
					$defaults['action'] = 'getcssimage';
					return $defaults;
				}
				break;
			case 'js':
				break;
				
			case 'js_localize.php':
				$defaults['action'] = 'localize';
				return $defaults;
			case 'browse.php':
				$defaults['action'] = 'browse';
				return $defaults;
				
			default:
				throw new Zend_Exception('invalid argument in router');
		}
		
		//  /js/
		switch( $pathex[1] )
		{
			case 'browser':
				if($pathex[2] == 'joiner.php')
				{
					$defaults['action'] = 'getjoiner';
				}
				break;
		}
		
		return $defaults;
	}
	
}